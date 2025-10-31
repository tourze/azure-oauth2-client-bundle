<?php

declare(strict_types=1);

namespace Tourze\AzureOAuth2ClientBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\AzureOAuth2ClientBundle\Exception\AzureOAuth2Exception;
use Tourze\AzureOAuth2ClientBundle\Exception\AzureOAuth2RuntimeException;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;

/**
 * @internal
 */
#[CoversClass(AzureOAuth2RuntimeException::class)]
class AzureOAuth2RuntimeExceptionTest extends AbstractExceptionTestCase
{
    public function testExceptionInheritance(): void
    {
        $exception = new AzureOAuth2RuntimeException('Test message');

        self::assertSame('Test message', $exception->getMessage());
    }

    public function testExceptionWithCodeAndPrevious(): void
    {
        $previous = new \RuntimeException('Previous exception');
        $exception = new AzureOAuth2RuntimeException('Test message', 123, $previous);

        self::assertSame('Test message', $exception->getMessage());
        self::assertSame(123, $exception->getCode());
        self::assertSame($previous, $exception->getPrevious());
    }
}
