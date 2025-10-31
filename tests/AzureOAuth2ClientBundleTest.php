<?php

declare(strict_types=1);

namespace Tourze\AzureOAuth2ClientBundle\Tests;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use HttpClientBundle\HttpClientBundle;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Bundle\MonologBundle\MonologBundle;
use Tourze\AzureOAuth2ClientBundle\AzureOAuth2ClientBundle;
use Tourze\PHPUnitSymfonyKernelTest\AbstractBundleTestCase;

/**
 * @internal
 */
#[CoversClass(AzureOAuth2ClientBundle::class)]
#[RunTestsInSeparateProcesses]
class AzureOAuth2ClientBundleTest extends AbstractBundleTestCase
{
    public function testBundleDependencies(): void
    {
        $dependencies = AzureOAuth2ClientBundle::getBundleDependencies();

        $this->assertIsArray($dependencies);
        $this->assertArrayHasKey(DoctrineBundle::class, $dependencies);
        $this->assertArrayHasKey(MonologBundle::class, $dependencies);
        $this->assertArrayHasKey(HttpClientBundle::class, $dependencies);

        $this->assertSame(['all' => true], $dependencies[DoctrineBundle::class]);
        $this->assertSame(['all' => true], $dependencies[MonologBundle::class]);
        $this->assertSame(['all' => true], $dependencies[HttpClientBundle::class]);
    }

    public function testBundleIntegration(): void
    {
        // 测试Bundle在容器中的集成
        $kernel = self::createKernel();
        $kernel->boot();

        $container = $kernel->getContainer();

        // 验证公共服务已注册
        if (!$container->has('Tourze\AzureOAuth2ClientBundle\Service\AzureOAuth2Service')) {
            // 输出调试信息
            $availableServices = [];
            // 检查服务是否存在的替代方法
            try {
                $container->get('Tourze\AzureOAuth2ClientBundle\Service\AzureOAuth2Service');
            } catch (\Exception $e) {
                self::fail('AzureOAuth2Service not found: ' . $e->getMessage());
            }
        }

        // 验证Entity管理器可以正确处理Entity类
        $entityManager = $container->get('doctrine.orm.entity_manager');
        $this->assertNotNull($entityManager);

        // 验证Repository服务已注册
        $this->assertTrue($container->has('Tourze\AzureOAuth2ClientBundle\Repository\AzureOAuth2ConfigRepository'));
        $this->assertTrue($container->has('Tourze\AzureOAuth2ClientBundle\Repository\AzureOAuth2StateRepository'));
        $this->assertTrue($container->has('Tourze\AzureOAuth2ClientBundle\Repository\AzureOAuth2UserRepository'));

        // 验证Repository服务可以通过容器获取
        $configRepo = $container->get('Tourze\AzureOAuth2ClientBundle\Repository\AzureOAuth2ConfigRepository');
        $stateRepo = $container->get('Tourze\AzureOAuth2ClientBundle\Repository\AzureOAuth2StateRepository');
        $userRepo = $container->get('Tourze\AzureOAuth2ClientBundle\Repository\AzureOAuth2UserRepository');

        $this->assertInstanceOf('Tourze\AzureOAuth2ClientBundle\Repository\AzureOAuth2ConfigRepository', $configRepo);
        $this->assertInstanceOf('Tourze\AzureOAuth2ClientBundle\Repository\AzureOAuth2StateRepository', $stateRepo);
        $this->assertInstanceOf('Tourze\AzureOAuth2ClientBundle\Repository\AzureOAuth2UserRepository', $userRepo);
    }
}
