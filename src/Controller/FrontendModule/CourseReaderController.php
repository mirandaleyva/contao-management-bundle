<?php

namespace MirandaLeyva\ContaoCourseManagementBundle\Controller\FrontendModule;

use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\Database;
use Contao\ModuleModel;
use Contao\PageModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsFrontendModule(type: 'course_reader', category: 'courses', template: 'mod_course_reader')]
class CourseReaderController extends AbstractFrontendModuleController
{
  protected function getResponse(FragmentTemplate $template, ModuleModel $model, Request $request): Response
  {
    $courseId = $request->query->getInt('course');

    if ($courseId <= 0) {
      $template->set('empty', true);
      $template->set('message', 'Kein Kurs ausgewählt.');

      return $template->getResponse();
    }

    $courseResult = Database::getInstance()
      ->prepare("
                SELECT *
                FROM tl_course
                WHERE id = ?
                AND published = '1'
                LIMIT 1
            ")
      ->execute($courseId);

    if ($courseResult->numRows < 1) {
      $template->set('empty', true);
      $template->set('message', 'Der angeforderte Kurs wurde nicht gefunden.');

      return $template->getResponse();
    }

    $today = date('Y-m-d');

    $datesResult = Database::getInstance()
      ->prepare("
                SELECT *
                FROM tl_course_date
                WHERE pid = ?
                AND published = '1'
                AND end_date >= ?
                ORDER BY start_date ASC, start_time ASC
            ")
      ->execute($courseId, $today);

    $dates = [];
    $registrationPage = null;

    if ($model->registration_jumpTo) {
      $jumpTo = PageModel::findPublishedById($model->registration_jumpTo);

      if ($jumpTo !== null) {
        $registrationPage = $jumpTo->getFrontendUrl();
      }
    }

    while ($datesResult->next()) {
      $addressParts = array_filter([
        trim(($datesResult->street ?: '') . ' ' . ($datesResult->house_number ?: '')),
        trim(($datesResult->postal_code ?: '') . ' ' . ($datesResult->venue ?: '')),
      ]);

      $registrationUrl = null;

      if ($registrationPage !== null && !$datesResult->fully_booked) {
        $registrationUrl = $registrationPage . '?course=' . $courseId . '&date=' . $datesResult->id;
      }

      $dates[] = [
        'id' => $datesResult->id,
        'start_date' => $datesResult->start_date,
        'end_date' => $datesResult->end_date,
        'add_time' => (bool) $datesResult->add_time,
        'start_time' => $datesResult->start_time,
        'end_time' => $datesResult->end_time,
        'location' => implode(', ', $addressParts),
        'fully_booked' => (bool) $datesResult->fully_booked,
        'registration_url' => $registrationUrl,
      ];
    }

    $course = [
      'id' => $courseResult->id,
      'title' => $courseResult->title,
      'author' => $courseResult->author,
      'description' => $courseResult->description,
      'preview_image' => $courseResult->preview_image,
      'form_reference' => $courseResult->form_reference,
      'dates' => $dates,
    ];

    $template->set('empty', false);
    $template->set('course', $course);

    return $template->getResponse();
  }
}
