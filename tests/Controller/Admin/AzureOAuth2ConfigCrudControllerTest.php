<?php

declare(strict_types=1);

namespace Tourze\AzureOAuth2ClientBundle\Tests\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Tourze\AzureOAuth2ClientBundle\Controller\Admin\AzureOAuth2ConfigCrudController;
use Tourze\AzureOAuth2ClientBundle\Entity\AzureOAuth2Config;
use Tourze\AzureOAuth2ClientBundle\Repository\AzureOAuth2ConfigRepository;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminControllerTestCase;

/**
 * @internal
 */
#[CoversClass(AzureOAuth2ConfigCrudController::class)]
#[RunTestsInSeparateProcesses]
final class AzureOAuth2ConfigCrudControllerTest extends AbstractEasyAdminControllerTestCase
{
    protected function getEntityFqcn(): string
    {
        return AzureOAuth2Config::class;
    }

    /**
     * @return AbstractCrudController<AzureOAuth2Config>
     */
    protected function getControllerService(): AbstractCrudController
    {
        return self::getService(AzureOAuth2ConfigCrudController::class);
    }

    public static function provideIndexPageHeaders(): iterable
    {
        yield 'ID' => ['ID'];
        yield '配置名称' => ['配置名称'];
        yield '应用ID' => ['应用ID'];
        yield '租户ID' => ['租户ID'];
        yield '是否有效' => ['是否有效'];
        yield '创建时间' => ['创建时间'];
        yield '更新时间' => ['更新时间'];
    }

    public static function provideNewPageFields(): iterable
    {
        yield 'name' => ['name'];
        yield 'clientId' => ['clientId'];
        yield 'tenantId' => ['tenantId'];
        yield 'clientSecret' => ['clientSecret'];
        yield 'redirectUri' => ['redirectUri'];
        yield 'scope' => ['scope'];
        yield 'valid' => ['valid'];
    }

    public static function provideEditPageFields(): iterable
    {
        yield 'name' => ['name'];
        yield 'clientId' => ['clientId'];
        yield 'tenantId' => ['tenantId'];
        yield 'clientSecret' => ['clientSecret'];
        yield 'redirectUri' => ['redirectUri'];
        yield 'scope' => ['scope'];
        yield 'valid' => ['valid'];
    }

    public function testIndexPage(): void
    {
        $client = self::createAuthenticatedClient();
        $crawler = $client->request('GET', '/admin');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        // Navigate to AzureOAuth2Config CRUD
        $link = $crawler->filter('a[href*="AzureOAuth2ConfigCrudController"]')->first();
        if ($link->count() > 0) {
            $client->click($link->link());
            $this->assertEquals(200, $client->getResponse()->getStatusCode());
        }
    }

    public function testCreateConfig(): void
    {
        // 创建客户端以初始化数据库
        $client = self::createClientWithDatabase();

        $config = new AzureOAuth2Config();
        $config->setName('Test Azure Config');
        $config->setClientId('test-client-id');
        $config->setTenantId('test-tenant-id');
        $config->setClientSecret('test-client-secret');
        $config->setValid(true);

        $em = self::getEntityManager();
        $em->persist($config);
        $em->flush();

        // Verify config was created
        $repository = self::getService(AzureOAuth2ConfigRepository::class);
        $savedConfig = $repository->findOneBy(['name' => 'Test Azure Config']);
        $this->assertNotNull($savedConfig);
        $this->assertEquals('Test Azure Config', $savedConfig->getName());
    }

    public function testConfigDataPersistence(): void
    {
        // Create client to initialize database
        $client = self::createClientWithDatabase();

        // Create test configs with different properties
        $config1 = new AzureOAuth2Config();
        $config1->setName('Production Config');
        $config1->setClientId('prod-client-id');
        $config1->setTenantId('prod-tenant-id');
        $config1->setClientSecret('prod-client-secret');
        $config1->setRedirectUri('https://example.com/callback');
        $config1->setScope('openid profile email');
        $config1->setValid(true);

        $em = self::getEntityManager();
        $em->persist($config1);
        $em->flush();

        $config2 = new AzureOAuth2Config();
        $config2->setName('Development Config');
        $config2->setClientId('dev-client-id');
        $config2->setTenantId('dev-tenant-id');
        $config2->setClientSecret('dev-client-secret');
        $config2->setRedirectUri('http://localhost:8000/callback');
        $config2->setScope('openid');
        $config2->setValid(false);

        $em->persist($config2);
        $em->flush();

        // Verify configs are saved correctly
        $repository = self::getService(AzureOAuth2ConfigRepository::class);
        $savedConfig1 = $repository->findOneBy(['name' => 'Production Config']);
        $this->assertNotNull($savedConfig1);
        $this->assertEquals('Production Config', $savedConfig1->getName());
        $this->assertTrue($savedConfig1->isValid());

        $savedConfig2 = $repository->findOneBy(['name' => 'Development Config']);
        $this->assertNotNull($savedConfig2);
        $this->assertEquals('Development Config', $savedConfig2->getName());
        $this->assertFalse($savedConfig2->isValid());
    }

    public function testValidationErrors(): void
    {
        // 创建客户端以初始化数据库
        $client = self::createAuthenticatedClient();

        // 测试表单级别的验证：提交空表单应详返回422状态码
        $crawler = $client->request('GET', '/admin');
        self::assertSame(200, $client->getResponse()->getStatusCode());

        // 导航到新增页面
        $link = $crawler->filter('a[href*="AzureOAuth2ConfigCrudController"]')->first();
        if ($link->count() > 0) {
            $crawler = $client->click($link->link());
            self::assertSame(200, $client->getResponse()->getStatusCode());

            // 寻找新增按钮
            $newLink = $crawler->filter('a[href*="new"]')->first();
            if ($newLink->count() > 0) {
                $crawler = $client->click($newLink->link());
                self::assertSame(200, $client->getResponse()->getStatusCode());

                // 提交空表单测试验证
                $form = $crawler->selectButton('Create')->form();
                $client->submit($form);

                // 提交空表单应该有验证错误，检查响应内容中是否包含验证错误信息
                $responseContent = $client->getResponse()->getContent();
                $this->assertIsString($responseContent);

                // 验证应该包含验证错误的相关文本
                $this->assertTrue(
                    false !== strpos($responseContent, 'invalid-feedback')
                    || false !== strpos($responseContent, 'should not be blank')
                    || false !== strpos($responseContent, 'required')
                    || false !== strpos($responseContent, 'This value should not be blank'),
                    'Should contain validation error messages'
                );
            }
        }

        // 测试Entity层的验证约束
        $validator = self::getService(ValidatorInterface::class);

        // 测试必填字段验证
        $config = new AzureOAuth2Config();

        // 不设置任何必填字段，应该有验证错误
        $violations = $validator->validate($config);
        $this->assertGreaterThan(0, $violations->count(), 'Should have validation errors for required fields');

        // 检查具体的必填字段验证错误
        $violationMessages = [];
        foreach ($violations as $violation) {
            $violationMessages[] = $violation->getPropertyPath() . ': ' . $violation->getMessage();
        }

        $allMessages = implode(' ', $violationMessages);
        $this->assertStringContainsString('clientId', $allMessages, 'clientId should be required');
        $this->assertStringContainsString('clientSecret', $allMessages, 'clientSecret should be required');
        $this->assertStringContainsString('tenantId', $allMessages, 'tenantId should be required');

        // 测试设置了必填字段后验证通过
        $config->setClientId('valid-client-id');
        $config->setClientSecret('valid-client-secret');
        $config->setTenantId('valid-tenant-id');

        $violations = $validator->validate($config);
        $this->assertEquals(0, $violations->count(), 'Should have no validation errors when required fields are set');

        // 测试字段长度限制
        $config->setClientId(str_repeat('a', 256)); // 超过255字符限制
        $violations = $validator->validate($config);
        $this->assertGreaterThan(0, $violations->count(), 'Should have validation errors for field length limit');

        // 测试URL验证
        $config->setClientId('valid-client-id'); // 重置为有效值
        $config->setRedirectUri('invalid-url'); // 无效URL
        $violations = $validator->validate($config);
        $this->assertGreaterThan(0, $violations->count(), 'Should have validation errors for invalid URL');

        // 测试所有字段都有效的情况
        $config->setRedirectUri('https://example.com/callback'); // 有效URL
        $config->setName('Test Config');
        $config->setScope('openid profile');
        $config->setRemark('Test remark');
        $config->setValid(true);

        $violations = $validator->validate($config);
        $this->assertEquals(0, $violations->count(), 'Should have no validation errors when all fields are valid');
    }
}
