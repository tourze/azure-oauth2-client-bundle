<?php

declare(strict_types=1);

namespace Tourze\AzureOAuth2ClientBundle\Tests\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\AzureOAuth2ClientBundle\Controller\Admin\AzureOAuth2UserCrudController;
use Tourze\AzureOAuth2ClientBundle\Entity\AzureOAuth2Config;
use Tourze\AzureOAuth2ClientBundle\Entity\AzureOAuth2User;
use Tourze\AzureOAuth2ClientBundle\Repository\AzureOAuth2UserRepository;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminControllerTestCase;

/**
 * @internal
 */
#[CoversClass(AzureOAuth2UserCrudController::class)]
#[RunTestsInSeparateProcesses]
final class AzureOAuth2UserCrudControllerTest extends AbstractEasyAdminControllerTestCase
{
    protected function getEntityFqcn(): string
    {
        return AzureOAuth2User::class;
    }

    /** @return AbstractCrudController<AzureOAuth2User> */
    protected function getControllerService(): AbstractCrudController
    {
        return self::getService(AzureOAuth2UserCrudController::class);
    }

    public static function provideIndexPageHeaders(): iterable
    {
        yield 'ID' => ['ID'];
        yield '配置' => ['配置'];
        yield '用户对象ID' => ['用户对象ID'];
        yield '用户主名称' => ['用户主名称'];
        yield '显示名称' => ['显示名称'];
        yield '邮箱' => ['邮箱'];
        yield '令牌过期时间' => ['令牌过期时间'];
        yield '创建时间' => ['创建时间'];
    }

    public static function provideNewPageFields(): iterable
    {
        yield 'config' => ['config'];
        yield 'objectId' => ['objectId'];
        yield 'displayName' => ['displayName'];
        yield 'userPrincipalName' => ['userPrincipalName'];
        yield 'mail' => ['mail'];
        yield 'accessToken' => ['accessToken'];
        yield 'tokenType' => ['tokenType'];
        yield 'expiresIn' => ['expiresIn'];
        yield 'refreshToken' => ['refreshToken'];
        yield 'idToken' => ['idToken'];
    }

    public static function provideEditPageFields(): iterable
    {
        // EDIT action is disabled for this controller, but we need at least one item for DataProvider
        yield 'disabled' => ['disabled'];
    }

    public function testIndexPage(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);
        $crawler = $client->request('GET', '/admin');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        // Navigate to AzureOAuth2User CRUD
        $link = $crawler->filter('a[href*="AzureOAuth2UserCrudController"]')->first();
        if ($link->count() > 0) {
            $client->click($link->link());
            $this->assertEquals(200, $client->getResponse()->getStatusCode());
        }
    }

    public function testCreateUser(): void
    {
        // 创建客户端以初始化数据库
        $client = self::createClientWithDatabase();

        // Create a test config first
        $config = new AzureOAuth2Config();
        $config->setName('Test Config');
        $config->setClientId('test-client-id');
        $config->setTenantId('test-tenant-id');
        $config->setClientSecret('test-client-secret');
        $config->setValid(true);

        $em = self::getEntityManager();
        $em->persist($config);
        $em->flush();

        // Create user with config
        $user = new AzureOAuth2User();
        $user->setConfig($config);
        $user->setObjectId('test-object-id-' . uniqid());
        $user->setDisplayName('Test User');
        $user->setMail('test@example.com');
        $user->setAccessToken('test-access-token');
        $user->setExpiresIn(3600); // This automatically sets tokenExpiresTime

        $em->persist($user);
        $em->flush();

        // Verify user was created
        $repository = self::getService(AzureOAuth2UserRepository::class);
        $savedUser = $repository->findOneBy(['objectId' => $user->getObjectId()]);
        $this->assertNotNull($savedUser);
        $this->assertEquals('Test User', $savedUser->getDisplayName());
    }

    public function testUserDataPersistence(): void
    {
        // Create client to initialize database
        $client = self::createClientWithDatabase();

        // Create test configs first
        $config1 = new AzureOAuth2Config();
        $config1->setName('Production Config');
        $config1->setClientId('prod-client-id');
        $config1->setTenantId('prod-tenant-id');
        $config1->setClientSecret('prod-client-secret');
        $config1->setValid(true);

        $config2 = new AzureOAuth2Config();
        $config2->setName('Development Config');
        $config2->setClientId('dev-client-id');
        $config2->setTenantId('dev-tenant-id');
        $config2->setClientSecret('dev-client-secret');
        $config2->setValid(true);

        $em = self::getEntityManager();
        $em->persist($config1);
        $em->persist($config2);
        $em->flush();

        // Create test users with different properties
        $user1 = new AzureOAuth2User();
        $user1->setConfig($config1);
        $user1->setObjectId('prod-user-' . uniqid());
        $user1->setDisplayName('Production User');
        $user1->setMail('prod@example.com');
        $user1->setAccessToken('prod-access-token');
        $user1->setRefreshToken('prod-refresh-token');
        $user1->setIdToken('prod-id-token');
        $user1->setExpiresIn(7200); // 2 hours

        $em->persist($user1);
        $em->flush();

        $user2 = new AzureOAuth2User();
        $user2->setConfig($config2);
        $user2->setObjectId('dev-user-' . uniqid());
        $user2->setDisplayName('Development User');
        $user2->setMail('dev@example.com');
        $user2->setAccessToken('dev-access-token');
        $user2->setExpiresIn(3600); // 1 hour

        $em->persist($user2);
        $em->flush();

        // Verify users are saved correctly
        $repository = self::getService(AzureOAuth2UserRepository::class);
        $savedUser1 = $repository->findOneBy(['objectId' => $user1->getObjectId()]);
        $this->assertNotNull($savedUser1);
        $this->assertEquals('Production User', $savedUser1->getDisplayName());
        $this->assertEquals('prod@example.com', $savedUser1->getMail());

        $savedUser2 = $repository->findOneBy(['objectId' => $user2->getObjectId()]);
        $this->assertNotNull($savedUser2);
        $this->assertEquals('Development User', $savedUser2->getDisplayName());
        $this->assertEquals('dev@example.com', $savedUser2->getMail());
    }

    public function testUserTokenManagement(): void
    {
        // Create client to initialize database
        $client = self::createClientWithDatabase();

        // Create test config first
        $config = new AzureOAuth2Config();
        $config->setName('Test Config');
        $config->setClientId('test-client-id');
        $config->setTenantId('test-tenant-id');
        $config->setClientSecret('test-client-secret');
        $config->setValid(true);

        $em = self::getEntityManager();
        $em->persist($config);
        $em->flush();

        // Test token expiration scenarios
        $validUser = new AzureOAuth2User();
        $validUser->setConfig($config);
        $validUser->setObjectId('valid-user-' . uniqid());
        $validUser->setDisplayName('Valid Token User');
        $validUser->setMail('valid@example.com');
        $validUser->setAccessToken('valid-access-token');
        $validUser->setExpiresIn(3600); // 1 hour - valid token

        $em->persist($validUser);
        $em->flush();

        // Create a user with an already expired token (set expires in to a very small number)
        $expiredUser = new AzureOAuth2User();
        $expiredUser->setConfig($config);
        $expiredUser->setObjectId('expired-user-' . uniqid());
        $expiredUser->setDisplayName('Expired Token User');
        $expiredUser->setMail('expired@example.com');
        $expiredUser->setAccessToken('expired-access-token');
        $expiredUser->setExpiresIn(1); // 1 second - will be expired quickly

        $em->persist($expiredUser);
        $em->flush();

        // Wait a moment to ensure the token expires
        sleep(2);

        // Verify token expiration logic
        $repository = self::getService(AzureOAuth2UserRepository::class);
        $savedExpiredUser = $repository->findOneBy(['objectId' => $expiredUser->getObjectId()]);
        $savedValidUser = $repository->findOneBy(['objectId' => $validUser->getObjectId()]);

        $this->assertNotNull($savedExpiredUser);
        $this->assertNotNull($savedValidUser);

        // Check token expiration status using the entity's method
        $this->assertTrue($savedExpiredUser->isTokenExpired());
        $this->assertFalse($savedValidUser->isTokenExpired());
    }
}
