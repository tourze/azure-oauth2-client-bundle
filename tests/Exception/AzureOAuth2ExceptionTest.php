<?php

declare(strict_types=1);

namespace Tourze\AzureOAuth2ClientBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\AzureOAuth2ClientBundle\Exception\AzureOAuth2Exception;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;

/**
 * @internal
 */
#[CoversClass(AzureOAuth2Exception::class)]
class AzureOAuth2ExceptionTest extends AbstractExceptionTestCase
{
    public function testBaseException(): void
    {
        // Test that AzureOAuth2Exception is properly defined as an abstract class
        $reflection = new \ReflectionClass(AzureOAuth2Exception::class);
        $this->assertTrue($reflection->isAbstract(), 'AzureOAuth2Exception should be abstract');
        $this->assertTrue($reflection->isSubclassOf(\Exception::class), 'AzureOAuth2Exception should extend Exception');
    }
}
