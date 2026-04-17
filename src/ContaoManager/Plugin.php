<?php

namespace MirandaLeyva\ContaoCourseManagementBundle\ContaoManager;

use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use MirandaLeyva\ContaoCourseManagementBundle\MirandaLeyvaContaoCourseManagementBundle;

class Plugin implements BundlePluginInterface
{
    public function getBundles(ParserInterface $parser): array
    {
        return [
            BundleConfig::create(MirandaLeyvaContaoCourseManagementBundle::class)
                ->setLoadAfter(['Contao\CoreBundle\ContaoCoreBundle']),
        ];
    }
}