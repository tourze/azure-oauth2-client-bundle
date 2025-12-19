<?php

declare(strict_types=1);

namespace Tourze\AzureOAuth2ClientBundle\DependencyInjection;

use Tourze\SymfonyDependencyServiceLoader\AutoExtension;

final class AzureOAuth2ClientExtension extends AutoExtension
{
    protected function getConfigDir(): string
    {
        return __DIR__ . '/../Resources/config';
    }
}
