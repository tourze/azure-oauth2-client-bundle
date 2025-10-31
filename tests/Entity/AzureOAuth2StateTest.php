<?php

declare(strict_types=1);

namespace Tourze\AzureOAuth2ClientBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\AzureOAuth2ClientBundle\Entity\AzureOAuth2Config;
use Tourze\AzureOAuth2ClientBundle\Entity\AzureOAuth2State;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * @internal
 */
#[CoversClass(AzureOAuth2State::class)]
class AzureOAuth2StateTest extends AbstractEntityTestCase
{
    private AzureOAuth2Config $config;

    protected function setUp(): void
    {
        $this->config = new AzureOAuth2Config();
        $this->config->setClientId('test-client-id');
        $this->config->setClientSecret('test-secret');
        $this->config->setTenantId('test-tenant-id');
    }

    protected function createEntity(): AzureOAuth2State
    {
        $state = new AzureOAuth2State();
        $state->setState('test-state');
        $state->setConfig($this->config);

        return $state;
    }

    public static function propertiesProvider(): iterable
    {
        yield 'state' => ['state', 'test-state-updated'];
        yield 'sessionId' => ['sessionId', 'test-session-id-updated'];
        yield 'codeChallenge' => ['codeChallenge', 'test-code-challenge-updated'];
        yield 'codeChallengeMethod' => ['codeChallengeMethod', 'S256'];
        yield 'used' => ['used', true];
        yield 'expiresTime' => ['expiresTime', new \DateTimeImmutable('2024-12-31 23:59:59')];
    }

    public function testCreateState(): void
    {
        $state = new AzureOAuth2State();
        $state->setState('test-state');
        $state->setConfig($this->config);

        $this->assertNull($state->getId());
        $this->assertSame('test-state', $state->getState());
        $this->assertSame($this->config, $state->getConfig());
        $this->assertFalse($state->isUsed());
        $this->assertNull($state->getCreateTime()); // TimestampableAware starts with null
        $this->assertNull($state->getUpdateTime()); // TimestampableAware starts with null
        $this->assertInstanceOf(\DateTimeImmutable::class, $state->getExpiresTime());
    }

    public function testSettersAndGetters(): void
    {
        $state = new AzureOAuth2State();
        $state->setState('test-state');
        $state->setConfig($this->config);

        $state->setSessionId('test-session-id');
        $state->setCodeChallenge('test-code-challenge');
        $state->setCodeChallengeMethod('S256');
        $state->setUsed(true);

        $newExpiresTime = (new \DateTimeImmutable())->modify('+5 minutes');
        $state->setExpiresTime($newExpiresTime);

        $this->assertSame('test-session-id', $state->getSessionId());
        $this->assertSame('test-code-challenge', $state->getCodeChallenge());
        $this->assertSame('S256', $state->getCodeChallengeMethod());
        $this->assertTrue($state->isUsed());
        $this->assertSame($newExpiresTime, $state->getExpiresTime());
    }

    public function testIsValid(): void
    {
        $state = new AzureOAuth2State();
        $state->setState('test-state');
        $state->setConfig($this->config);

        // Fresh state should be valid
        $this->assertTrue($state->isValid());

        // Used state should be invalid
        $state->setUsed(true);
        $this->assertFalse($state->isValid());

        // Expired state should be invalid
        $state->setUsed(false);
        $pastTime = (new \DateTimeImmutable())->modify('-1 minute');
        $state->setExpiresTime($pastTime);
        $this->assertFalse($state->isValid());
    }

    public function testToString(): void
    {
        $state = new AzureOAuth2State();
        $state->setState('test-state');
        $state->setConfig($this->config);

        $this->assertSame('AzureOAuth2State[]:test-state', $state->__toString());
    }
}
