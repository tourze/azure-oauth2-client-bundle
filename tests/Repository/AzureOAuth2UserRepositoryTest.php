<?php

declare(strict_types=1);

namespace Tourze\AzureOAuth2ClientBundle\Tests\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\AzureOAuth2ClientBundle\Entity\AzureOAuth2Config;
use Tourze\AzureOAuth2ClientBundle\Entity\AzureOAuth2User;
use Tourze\AzureOAuth2ClientBundle\Repository\AzureOAuth2ConfigRepository;
use Tourze\AzureOAuth2ClientBundle\Repository\AzureOAuth2UserRepository;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;

/**
 * @internal
 */
#[CoversClass(AzureOAuth2UserRepository::class)]
#[RunTestsInSeparateProcesses]
class AzureOAuth2UserRepositoryTest extends AbstractRepositoryTestCase
{
    /**
     * @return ServiceEntityRepository<AzureOAuth2User>
     */
    protected function getRepository(): ServiceEntityRepository
    {
        return self::getService(AzureOAuth2UserRepository::class);
    }

    protected function createNewEntity(): AzureOAuth2User
    {
        $config = $this->createConfig();
        $user = new AzureOAuth2User();
        $user->setObjectId('test-object-' . uniqid());
        $user->setConfig($config);
        $user->setAccessToken('test-access-token-' . uniqid());
        $user->setExpiresIn(3600);

        return $user;
    }

    protected function onSetUp(): void
    {
        // Create initial data to satisfy testCountWithDataFixtureShouldReturnGreaterThanZero
        $config = $this->createConfig();
        $user = new AzureOAuth2User();
        $user->setConfig($config);
        $user->setObjectId('setup-user-' . uniqid());
        $user->setAccessToken('setup-access-token');
        $user->setExpiresIn(3600);
        /** @var AzureOAuth2UserRepository $repository */
        $repository = $this->getRepository();
        $repository->save($user, true);
    }

    private function createConfig(): AzureOAuth2Config
    {
        $configRepository = self::getService(AzureOAuth2ConfigRepository::class);

        $config = new AzureOAuth2Config();
        $config->setClientId('test-client-' . uniqid());
        $config->setClientSecret('test-secret-' . uniqid());
        $config->setTenantId('test-tenant-' . uniqid());
        $config->setValid(true);

        $configRepository->save($config, true);

        return $config;
    }

    public function testFindByObjectId(): void
    {
        /** @var AzureOAuth2UserRepository $repository */
        $repository = $this->getRepository();

        $user = $this->createNewEntity();
        $repository->save($user, true);

        $result = $repository->findByObjectId($user->getObjectId());

        $this->assertInstanceOf(AzureOAuth2User::class, $result);
        $this->assertSame($user->getObjectId(), $result->getObjectId());
    }

    public function testFindByObjectIdReturnsNull(): void
    {
        /** @var AzureOAuth2UserRepository $repository */
        $repository = $this->getRepository();

        $result = $repository->findByObjectId('non-existent-object-id');

        $this->assertNull($result);
    }

    public function testFindByUserPrincipalName(): void
    {
        /** @var AzureOAuth2UserRepository $repository */
        $repository = $this->getRepository();

        $user = $this->createNewEntity();
        $user->setUserPrincipalName('test@example.com');
        $repository->save($user, true);

        $result = $repository->findByUserPrincipalName('test@example.com');

        $this->assertInstanceOf(AzureOAuth2User::class, $result);
        $this->assertSame('test@example.com', $result->getUserPrincipalName());
    }

    public function testFindByUserPrincipalNameReturnsNull(): void
    {
        /** @var AzureOAuth2UserRepository $repository */
        $repository = $this->getRepository();

        $result = $repository->findByUserPrincipalName('non-existent@example.com');

        $this->assertNull($result);
    }

    public function testFindByMail(): void
    {
        /** @var AzureOAuth2UserRepository $repository */
        $repository = $this->getRepository();

        $user = $this->createNewEntity();
        $user->setMail('user@company.com');
        $repository->save($user, true);

        $result = $repository->findByMail('user@company.com');

        $this->assertInstanceOf(AzureOAuth2User::class, $result);
        $this->assertSame('user@company.com', $result->getMail());
    }

    public function testFindByMailReturnsNull(): void
    {
        /** @var AzureOAuth2UserRepository $repository */
        $repository = $this->getRepository();

        $result = $repository->findByMail('non-existent@company.com');

        $this->assertNull($result);
    }

    public function testFindExpiredTokenUsers(): void
    {
        /** @var AzureOAuth2UserRepository $repository */
        $repository = $this->getRepository();

        // Create user with expired token and refresh token
        $expiredUser = $this->createNewEntity();
        $expiredUser->setExpiresIn(-3600); // Expired 1 hour ago
        $expiredUser->setRefreshToken('refresh-token-123');
        $repository->save($expiredUser, true);

        // Create user with valid token
        $validUser = $this->createNewEntity();
        $validUser->setExpiresIn(3600); // Valid for 1 hour
        $repository->save($validUser, true);

        // Create user with expired token but no refresh token
        $expiredUserNoRefresh = $this->createNewEntity();
        $expiredUserNoRefresh->setExpiresIn(-3600); // Expired 1 hour ago
        $expiredUserNoRefresh->setRefreshToken(null);
        $repository->save($expiredUserNoRefresh, true);

        $result = $repository->findExpiredTokenUsers();

        $this->assertIsArray($result);
        $this->assertContainsOnlyInstancesOf(AzureOAuth2User::class, $result);
        $this->assertContains($expiredUser, $result);
        $this->assertNotContains($validUser, $result);
        $this->assertNotContains($expiredUserNoRefresh, $result);
    }

    public function testUpdateOrCreateNewUser(): void
    {
        /** @var AzureOAuth2UserRepository $repository */
        $repository = $this->getRepository();

        $config = $this->createConfig();
        $userData = [
            'id' => 'new-object-id-123',
            'userPrincipalName' => 'newuser@example.com',
            'displayName' => 'New User',
            'givenName' => 'New',
            'surname' => 'User',
            'mail' => 'newuser@example.com',
            'mobilePhone' => '+1234567890',
            'officeLocation' => 'New York',
            'preferredLanguage' => 'en-US',
            'jobTitle' => 'Developer',
            'access_token' => 'new-access-token',
            'refresh_token' => 'new-refresh-token',
            'id_token' => 'new-id-token',
            'expires_in' => 3600,
            'scope' => 'openid profile email',
        ];

        $user = $repository->updateOrCreate($userData, $config);

        $this->assertInstanceOf(AzureOAuth2User::class, $user);
        $this->assertSame('new-object-id-123', $user->getObjectId());
        $this->assertSame('newuser@example.com', $user->getUserPrincipalName());
        $this->assertSame('New User', $user->getDisplayName());
        $this->assertSame('New', $user->getGivenName());
        $this->assertSame('User', $user->getSurname());
        $this->assertSame('newuser@example.com', $user->getMail());
        $this->assertSame('+1234567890', $user->getMobilePhone());
        $this->assertSame('New York', $user->getOfficeLocation());
        $this->assertSame('en-US', $user->getPreferredLanguage());
        $this->assertSame('Developer', $user->getJobTitle());
        $this->assertSame('new-access-token', $user->getAccessToken());
        $this->assertSame('new-refresh-token', $user->getRefreshToken());
        $this->assertSame('new-id-token', $user->getIdToken());
        $this->assertSame(3600, $user->getExpiresIn());
        $this->assertSame('openid profile email', $user->getScope());
        $this->assertSame($userData, $user->getRawData());
        $this->assertSame($config, $user->getConfig());
    }

    public function testUpdateOrCreateExistingUser(): void
    {
        /** @var AzureOAuth2UserRepository $repository */
        $repository = $this->getRepository();

        // Create existing user
        $existingUser = $this->createNewEntity();
        $existingUser->setDisplayName('Old Name');
        $repository->save($existingUser, true);

        $config = $existingUser->getConfig();
        $userData = [
            'id' => $existingUser->getObjectId(),
            'displayName' => 'Updated Name',
            'access_token' => 'updated-access-token',
            'expires_in' => 7200,
        ];

        $updatedUser = $repository->updateOrCreate($userData, $config);

        $this->assertSame($existingUser, $updatedUser);
        $this->assertSame('Updated Name', $updatedUser->getDisplayName());
        $this->assertSame('updated-access-token', $updatedUser->getAccessToken());
        $this->assertSame(7200, $updatedUser->getExpiresIn());
    }

    public function testUpdateOrCreateWithAlternativeObjectIdFields(): void
    {
        /** @var AzureOAuth2UserRepository $repository */
        $repository = $this->getRepository();

        $config = $this->createConfig();

        // Test with 'oid' field
        $userData1 = [
            'oid' => 'object-id-from-oid',
            'access_token' => 'token-1',
            'expires_in' => 3600,
        ];

        $user1 = $repository->updateOrCreate($userData1, $config);
        $this->assertSame('object-id-from-oid', $user1->getObjectId());

        // Test with 'objectId' field
        $userData2 = [
            'objectId' => 'object-id-from-objectId',
            'access_token' => 'token-2',
            'expires_in' => 3600,
        ];

        $user2 = $repository->updateOrCreate($userData2, $config);
        $this->assertSame('object-id-from-objectId', $user2->getObjectId());
    }

    public function testUpdateOrCreateThrowsExceptionWhenObjectIdMissing(): void
    {
        /** @var AzureOAuth2UserRepository $repository */
        $repository = $this->getRepository();

        $config = $this->createConfig();
        $userData = [
            'displayName' => 'Test User',
            'access_token' => 'test-token',
            'expires_in' => 3600,
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required field: objectId');

        $repository->updateOrCreate($userData, $config);
    }

    public function testSave(): void
    {
        /** @var AzureOAuth2UserRepository $repository */
        $repository = $this->getRepository();

        $user = $this->createNewEntity();

        // Test save without flush
        $repository->save($user, false);
        // ID won't be assigned until flush, so we manually flush to verify persistence worked
        self::getEntityManager()->flush();
        $this->assertNotNull($user->getId());

        // Test save with flush (default)
        $user2 = $this->createNewEntity();
        $repository->save($user2);
        $this->assertNotNull($user2->getId());

        // Verify both users are persisted
        $allUsers = $repository->findAll();
        $this->assertContains($user, $allUsers);
        $this->assertContains($user2, $allUsers);
    }

    public function testRemove(): void
    {
        /** @var AzureOAuth2UserRepository $repository */
        $repository = $this->getRepository();

        $user = $this->createNewEntity();
        $repository->save($user, true);

        $userId = $user->getId();
        $this->assertNotNull($userId);

        // Test remove without flush
        $repository->remove($user, false);

        // User should still exist until flush
        $found = $repository->find($userId);
        $this->assertNotNull($found);

        // Flush to complete removal
        self::getEntityManager()->flush();

        // Now user should be gone
        $found = $repository->find($userId);
        $this->assertNull($found);
    }

    public function testRemoveWithDefaultFlush(): void
    {
        /** @var AzureOAuth2UserRepository $repository */
        $repository = $this->getRepository();

        $user = $this->createNewEntity();
        $repository->save($user, true);

        $userId = $user->getId();
        $this->assertNotNull($userId);

        // Test remove with flush (default)
        $repository->remove($user);

        // User should be immediately gone
        $found = $repository->find($userId);
        $this->assertNull($found);
    }

    public function testUpdateOrCreateHandlesNullValues(): void
    {
        /** @var AzureOAuth2UserRepository $repository */
        $repository = $this->getRepository();

        $config = $this->createConfig();
        $userData = [
            'id' => 'test-object-id',
            'userPrincipalName' => null,
            'displayName' => null,
            'mail' => null,
            'refresh_token' => null,
            'id_token' => null,
            'scope' => null,
            'access_token' => 'test-token',
            'expires_in' => 3600,
        ];

        $user = $repository->updateOrCreate($userData, $config);

        $this->assertInstanceOf(AzureOAuth2User::class, $user);
        $this->assertNull($user->getUserPrincipalName());
        $this->assertNull($user->getDisplayName());
        $this->assertNull($user->getMail());
        $this->assertNull($user->getRefreshToken());
        $this->assertNull($user->getIdToken());
        $this->assertNull($user->getScope());
        $this->assertSame('test-token', $user->getAccessToken());
    }

    public function testUpdateOrCreateIgnoresInvalidDataTypes(): void
    {
        /** @var AzureOAuth2UserRepository $repository */
        $repository = $this->getRepository();

        $config = $this->createConfig();
        $userData = [
            'id' => 'test-object-id',
            'userPrincipalName' => 123, // Invalid type - should be ignored
            'displayName' => [], // Invalid type - should be ignored
            'access_token' => 'test-token',
            'expires_in' => 3600, // Valid value
        ];

        $user = $repository->updateOrCreate($userData, $config);

        $this->assertInstanceOf(AzureOAuth2User::class, $user);
        $this->assertNull($user->getUserPrincipalName()); // Should remain null due to invalid type
        $this->assertNull($user->getDisplayName()); // Should remain null due to invalid type
        $this->assertSame(3600, $user->getExpiresIn()); // Should use the valid value
    }
}
