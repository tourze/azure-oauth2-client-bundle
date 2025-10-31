<?php

declare(strict_types=1);

namespace Tourze\AzureOAuth2ClientBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\AzureOAuth2ClientBundle\Exception\AzureOAuth2ConfigurationException;
use Tourze\AzureOAuth2ClientBundle\Exception\AzureOAuth2Exception;
use Tourze\AzureOAuth2ClientBundle\Service\AzureOAuth2Service;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(AzureOAuth2Service::class)]
#[RunTestsInSeparateProcesses]
class AzureOAuth2ServiceTest extends AbstractIntegrationTestCase
{
    private AzureOAuth2Service $service;

    protected function onSetUp(): void
    {
        // 从容器获取真实的服务实例，符合集成测试原则
        $this->service = self::getService(AzureOAuth2Service::class);
    }

    public function testGetBaseUrl(): void
    {
        $this->assertSame('https://graph.microsoft.com', $this->service->getBaseUrl());
    }

    public function testServiceCanBeInstantiated(): void
    {
        $this->assertInstanceOf(AzureOAuth2Service::class, $this->service);
    }

    public function testGenerateAuthorizationUrlBehavior(): void
    {
        // 集成测试：测试授权URL生成的基本行为
        // 如果数据库中没有配置，应该抛出异常
        try {
            $authUrl = $this->service->generateAuthorizationUrl();
            // 如果成功生成，验证URL格式
            $this->assertIsString($authUrl);
            $this->assertStringContainsString('login.microsoftonline.com', $authUrl);
        } catch (AzureOAuth2ConfigurationException $e) {
            // 如果没有配置，应该抛出适当的异常
            $this->assertStringContainsString('No valid Azure OAuth2 configuration found', $e->getMessage());
        }
    }

    public function testCleanupExpiredStates(): void
    {
        // 集成测试：测试清理过期状态的基本功能
        $result = $this->service->cleanupExpiredStates();
        $this->assertIsInt($result);
        $this->assertGreaterThanOrEqual(0, $result);
    }

    public function testRefreshExpiredTokens(): void
    {
        // 集成测试：测试刷新过期token的基本功能
        $result = $this->service->refreshExpiredTokens();
        $this->assertIsInt($result);
        $this->assertGreaterThanOrEqual(0, $result);
    }

    public function testFetchUserInfo(): void
    {
        // 集成测试：测试获取用户信息异常情况
        $this->expectException(AzureOAuth2Exception::class);
        $this->expectExceptionMessage('User not found');

        $this->service->fetchUserInfo('non-existent-user-id');
    }

    public function testHandleCallback(): void
    {
        // 集成测试：测试回调处理异常情况
        $this->expectException(AzureOAuth2Exception::class);
        $this->expectExceptionMessage('Invalid or expired state');

        $this->service->handleCallback('test-code', 'invalid-state');
    }

    public function testRefreshToken(): void
    {
        // 集成测试：测试刷新token失败情况
        $result = $this->service->refreshToken('non-existent-user-id');
        $this->assertFalse($result);
    }
}
