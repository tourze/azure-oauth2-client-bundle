<?php

declare(strict_types=1);

namespace Tourze\AzureOAuth2ClientBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\AzureOAuth2ClientBundle\Exception\AzureOAuth2ConfigurationException;
use Tourze\AzureOAuth2ClientBundle\Exception\AzureOAuth2Exception;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;

/**
 * @internal
 */
#[CoversClass(AzureOAuth2ConfigurationException::class)]
class AzureOAuth2ConfigurationExceptionTest extends AbstractExceptionTestCase
{
    public function testConfigurationException(): void
    {
        $exception = new AzureOAuth2ConfigurationException('Configuration error');

        $this->assertSame('Configuration error', $exception->getMessage());
        $this->assertInstanceOf(AzureOAuth2Exception::class, $exception);
    }

    public function testConfigurationExceptionWithCode(): void
    {
        $exception = new AzureOAuth2ConfigurationException('Configuration error with code', 500);

        $this->assertSame('Configuration error with code', $exception->getMessage());
        $this->assertSame(500, $exception->getCode());
    }
}
