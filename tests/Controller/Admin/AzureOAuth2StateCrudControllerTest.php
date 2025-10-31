<?php

declare(strict_types=1);

namespace Tourze\AzureOAuth2ClientBundle\Tests\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\AzureOAuth2ClientBundle\Controller\Admin\AzureOAuth2StateCrudController;
use Tourze\AzureOAuth2ClientBundle\Entity\AzureOAuth2Config;
use Tourze\AzureOAuth2ClientBundle\Entity\AzureOAuth2State;
use Tourze\AzureOAuth2ClientBundle\Repository\AzureOAuth2StateRepository;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminControllerTestCase;

/**
 * @internal
 */
#[CoversClass(AzureOAuth2StateCrudController::class)]
#[RunTestsInSeparateProcesses]
final class AzureOAuth2StateCrudControllerTest extends AbstractEasyAdminControllerTestCase
{
    protected function getEntityFqcn(): string
    {
        return AzureOAuth2State::class;
    }

    /** @return AbstractCrudController<AzureOAuth2State> */
    protected function getControllerService(): AbstractCrudController
    {
        return self::getService(AzureOAuth2StateCrudController::class);
    }

    public static function provideIndexPageHeaders(): iterable
    {
        yield 'ID' => ['ID'];
        yield 'OAuth2配置' => ['OAuth2配置'];
        yield '状态值' => ['状态值'];
        yield '是否已使用' => ['是否已使用'];
        yield '过期时间' => ['过期时间'];
        yield '创建时间' => ['创建时间'];
    }

    public static function provideNewPageFields(): iterable
    {
        yield 'state' => ['state'];
        yield 'config' => ['config'];
        yield 'sessionId' => ['sessionId'];
        yield 'redirectUri' => ['redirectUri'];
        yield 'codeChallenge' => ['codeChallenge'];
        yield 'codeChallengeMethod' => ['codeChallengeMethod'];
        yield 'accessToken' => ['accessToken'];
        yield 'tokenType' => ['tokenType'];
        yield 'expiresIn' => ['expiresIn'];
        yield 'refreshToken' => ['refreshToken'];
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

        // Navigate to AzureOAuth2State CRUD
        $link = $crawler->filter('a[href*="AzureOAuth2StateCrudController"]')->first();
        if ($link->count() > 0) {
            $client->click($link->link());
            $this->assertEquals(200, $client->getResponse()->getStatusCode());
        }
    }

    public function testCreateState(): void
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

        // Create state with config
        $state = new AzureOAuth2State();
        $state->setState('test-state-' . uniqid());
        $state->setConfig($config);
        $state->setSessionId('test-session');

        $em->persist($state);
        $em->flush();

        // Verify state was created
        $repository = self::getService(AzureOAuth2StateRepository::class);
        $savedState = $repository->findOneBy(['state' => $state->getState()]);
        $this->assertNotNull($savedState);
        $this->assertEquals($state->getState(), $savedState->getState());
    }

    public function testStateDataPersistence(): void
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

        // Create test states with different properties
        $state1 = new AzureOAuth2State();
        $state1->setState('production-state-' . uniqid());
        $state1->setConfig($config1);
        $state1->setSessionId('admin-session');
        $state1->setCodeChallenge('test-code-challenge');
        $state1->setCodeChallengeMethod('S256');

        $em->persist($state1);
        $em->flush();

        $state2 = new AzureOAuth2State();
        $state2->setState('development-state-' . uniqid());
        $state2->setConfig($config2);
        $state2->setSessionId('developer-session');

        $em->persist($state2);
        $em->flush();

        // Verify states are saved correctly
        $repository = self::getService(AzureOAuth2StateRepository::class);
        $savedState1 = $repository->findOneBy(['state' => $state1->getState()]);
        $this->assertNotNull($savedState1);
        $this->assertEquals('admin-session', $savedState1->getSessionId());
        $this->assertEquals('test-code-challenge', $savedState1->getCodeChallenge());

        $savedState2 = $repository->findOneBy(['state' => $state2->getState()]);
        $this->assertNotNull($savedState2);
        $this->assertEquals('developer-session', $savedState2->getSessionId());
    }
}
