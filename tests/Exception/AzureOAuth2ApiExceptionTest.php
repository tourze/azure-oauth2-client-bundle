<?php

declare(strict_types=1);

namespace Tourze\AzureOAuth2ClientBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\AzureOAuth2ClientBundle\Exception\AzureOAuth2ApiException;
use Tourze\AzureOAuth2ClientBundle\Exception\AzureOAuth2Exception;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;

/**
 * @internal
 */
#[CoversClass(AzureOAuth2ApiException::class)]
class AzureOAuth2ApiExceptionTest extends AbstractExceptionTestCase
{
    public function testApiException(): void
    {
        $exception = new AzureOAuth2ApiException('API error');

        $this->assertSame('API error', $exception->getMessage());
        $this->assertInstanceOf(AzureOAuth2Exception::class, $exception);
    }

    public function testApiExceptionWithCode(): void
    {
        $exception = new AzureOAuth2ApiException('API error with code', 400);

        $this->assertSame('API error with code', $exception->getMessage());
        $this->assertSame(400, $exception->getCode());
    }
}
