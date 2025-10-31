<?php

declare(strict_types=1);

namespace Tourze\AzureOAuth2ClientBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\AzureOAuth2ClientBundle\Entity\AzureOAuth2Config;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * @internal
 */
#[CoversClass(AzureOAuth2Config::class)]
class AzureOAuth2ConfigTest extends AbstractEntityTestCase
{
    protected function createEntity(): AzureOAuth2Config
    {
        $config = new AzureOAuth2Config();
        $config->setClientId('test-client-id');
        $config->setClientSecret('test-client-secret');
        $config->setTenantId('test-tenant-id');

        return $config;
    }

    public static function propertiesProvider(): iterable
    {
        yield 'clientId' => ['clientId', 'test-client-id-updated'];
        yield 'clientSecret' => ['clientSecret', 'test-client-secret-updated'];
        yield 'tenantId' => ['tenantId', 'test-tenant-id-updated'];
        yield 'name' => ['name', 'Test Config Name'];
        yield 'scope' => ['scope', 'openid profile email'];
        yield 'redirectUri' => ['redirectUri', 'https://example.com/callback'];
        yield 'remark' => ['remark', 'Test remark'];
        yield 'valid' => ['valid', false];
    }

    public function testCreateConfig(): void
    {
        $config = new AzureOAuth2Config();

        $this->assertNull($config->getId());
        $this->assertNull($config->getCreateTime()); // TimestampableAware starts with null
        $this->assertNull($config->getUpdateTime()); // TimestampableAware starts with null
        $this->assertTrue($config->isValid());
    }

    public function testSettersAndGetters(): void
    {
        $config = new AzureOAuth2Config();

        $config->setClientId('test-client-id');
        $config->setClientSecret('test-client-secret');
        $config->setTenantId('test-tenant-id');
        $config->setName('Test Config');
        $config->setScope('openid profile email');
        $config->setRedirectUri('https://example.com/callback');
        $config->setRemark('Test remark');
        $config->setValid(false);

        $this->assertSame('test-client-id', $config->getClientId());
        $this->assertSame('test-client-secret', $config->getClientSecret());
        $this->assertSame('test-tenant-id', $config->getTenantId());
        $this->assertSame('Test Config', $config->getName());
        $this->assertSame('openid profile email', $config->getScope());
        $this->assertSame('https://example.com/callback', $config->getRedirectUri());
        $this->assertSame('Test remark', $config->getRemark());
        $this->assertFalse($config->isValid());
    }

    public function testToString(): void
    {
        $config = new AzureOAuth2Config();
        $config->setClientId('test-client-id');

        $this->assertSame('AzureOAuth2Config[]:test-client-id', $config->__toString());
    }

    public function testUpdateTime(): void
    {
        $config = new AzureOAuth2Config();
        $originalTime = $config->getUpdateTime();

        usleep(1000); // 1ms

        $newTime = new \DateTimeImmutable();
        $config->setUpdateTime($newTime);

        $this->assertNotSame($originalTime, $config->getUpdateTime());
        $this->assertSame($newTime, $config->getUpdateTime());
    }
}
