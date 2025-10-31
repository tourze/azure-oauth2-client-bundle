<?php

declare(strict_types=1);

namespace Tourze\AzureOAuth2ClientBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\AzureOAuth2ClientBundle\Entity\AzureOAuth2Config;
use Tourze\AzureOAuth2ClientBundle\Entity\AzureOAuth2User;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * @internal
 */
#[CoversClass(AzureOAuth2User::class)]
class AzureOAuth2UserTest extends AbstractEntityTestCase
{
    private AzureOAuth2Config $config;

    protected function setUp(): void
    {
        $this->config = new AzureOAuth2Config();
        $this->config->setClientId('test-client-id');
        $this->config->setClientSecret('test-secret');
        $this->config->setTenantId('test-tenant-id');
    }

    protected function createEntity(): AzureOAuth2User
    {
        $user = new AzureOAuth2User();
        $user->setConfig($this->config);
        $user->setObjectId('test-object-id');
        $user->setAccessToken('test-access-token');
        $user->setExpiresIn(3600);

        return $user;
    }

    public static function propertiesProvider(): iterable
    {
        yield 'objectId' => ['objectId', 'test-object-id-updated'];
        yield 'userPrincipalName' => ['userPrincipalName', 'user@example.org'];
        yield 'displayName' => ['displayName', 'Test User Updated'];
        yield 'givenName' => ['givenName', 'TestUpdated'];
        yield 'surname' => ['surname', 'UserUpdated'];
        yield 'mail' => ['mail', 'user.updated@example.com'];
        yield 'mobilePhone' => ['mobilePhone', '+9876543210'];
        yield 'officeLocation' => ['officeLocation', 'Building 2'];
        yield 'preferredLanguage' => ['preferredLanguage', 'zh-CN'];
        yield 'jobTitle' => ['jobTitle', 'Senior Developer'];
        yield 'accessToken' => ['accessToken', 'test-access-token-updated'];
        yield 'refreshToken' => ['refreshToken', 'test-refresh-token-updated'];
        yield 'idToken' => ['idToken', 'test-id-token-updated'];
        yield 'expiresIn' => ['expiresIn', 7200];
        yield 'scope' => ['scope', 'openid profile email offline_access'];
        yield 'rawData' => ['rawData', ['updated' => 'test-data']];
    }

    public function testCreateUser(): void
    {
        $user = new AzureOAuth2User();

        $this->assertNull($user->getId());
        $this->assertNull($user->getCreateTime()); // TimestampableAware starts with null
        $this->assertNull($user->getUpdateTime()); // TimestampableAware starts with null
    }

    public function testSettersAndGetters(): void
    {
        $user = new AzureOAuth2User();

        $user->setConfig($this->config);
        $user->setObjectId('test-object-id');
        $user->setUserPrincipalName('user@example.com');
        $user->setDisplayName('Test User');
        $user->setGivenName('Test');
        $user->setSurname('User');
        $user->setMail('user@example.com');
        $user->setMobilePhone('+1234567890');
        $user->setOfficeLocation('Building 1');
        $user->setPreferredLanguage('en-US');
        $user->setJobTitle('Developer');
        $user->setAccessToken('test-access-token');
        $user->setRefreshToken('test-refresh-token');
        $user->setIdToken('test-id-token');
        $user->setExpiresIn(3600);
        $user->setScope('openid profile email');
        $user->setRawData(['test' => 'data']);

        $this->assertSame($this->config, $user->getConfig());
        $this->assertSame('test-object-id', $user->getObjectId());
        $this->assertSame('user@example.com', $user->getUserPrincipalName());
        $this->assertSame('Test User', $user->getDisplayName());
        $this->assertSame('Test', $user->getGivenName());
        $this->assertSame('User', $user->getSurname());
        $this->assertSame('user@example.com', $user->getMail());
        $this->assertSame('+1234567890', $user->getMobilePhone());
        $this->assertSame('Building 1', $user->getOfficeLocation());
        $this->assertSame('en-US', $user->getPreferredLanguage());
        $this->assertSame('Developer', $user->getJobTitle());
        $this->assertSame('test-access-token', $user->getAccessToken());
        $this->assertSame('test-refresh-token', $user->getRefreshToken());
        $this->assertSame('test-id-token', $user->getIdToken());
        $this->assertSame(3600, $user->getExpiresIn());
        $this->assertSame('openid profile email', $user->getScope());
        $this->assertSame(['test' => 'data'], $user->getRawData());
    }

    public function testTokenExpiration(): void
    {
        $user = new AzureOAuth2User();

        // Set token to expire in 1 second
        $user->setExpiresIn(1);
        $this->assertFalse($user->isTokenExpired());

        // Wait and check expiration
        sleep(2);
        $this->assertTrue($user->isTokenExpired());
    }

    public function testToString(): void
    {
        $user = new AzureOAuth2User();
        $user->setObjectId('test-object-id');

        $this->assertSame('AzureOAuth2User[]:test-object-id', $user->__toString());
    }
}
