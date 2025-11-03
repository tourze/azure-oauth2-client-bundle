<?php

declare(strict_types=1);

namespace Tourze\AzureOAuth2ClientBundle;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use HttpClientBundle\HttpClientBundle;
use Symfony\Bundle\MonologBundle\MonologBundle;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Tourze\BundleDependency\BundleDependencyInterface;
use Tourze\EasyAdminMenuBundle\EasyAdminMenuBundle;

class AzureOAuth2ClientBundle extends Bundle implements BundleDependencyInterface
{
    public static function getBundleDependencies(): array
    {
        return [
            DoctrineBundle::class => ['all' => true],
            MonologBundle::class => ['all' => true],
            HttpClientBundle::class => ['all' => true],
            EasyAdminMenuBundle::class => ['all' => true],
        ];
    }
}
