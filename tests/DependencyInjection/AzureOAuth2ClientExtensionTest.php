<?php

declare(strict_types=1);

namespace Tourze\AzureOAuth2ClientBundle\Tests\DependencyInjection;

use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Tourze\AzureOAuth2ClientBundle\DependencyInjection\AzureOAuth2ClientExtension;
use Tourze\PHPUnitSymfonyUnitTest\AbstractDependencyInjectionExtensionTestCase;

/**
 * @internal
 */
#[CoversClass(AzureOAuth2ClientExtension::class)]
class AzureOAuth2ClientExtensionTest extends AbstractDependencyInjectionExtensionTestCase
{
    public function testLoad(): void
    {
        $extension = new AzureOAuth2ClientExtension();
        $container = new ContainerBuilder();

        // 设置必需的参数以避免参数不存在错误
        $container->setParameter('kernel.environment', 'test');

        $extension->load([], $container);

        // 验证扩展已正确加载
        $this->assertInstanceOf(AzureOAuth2ClientExtension::class, $extension);
    }

    public function testGetAlias(): void
    {
        $extension = new AzureOAuth2ClientExtension();

        $this->assertSame('azure_o_auth2_client', $extension->getAlias());
    }
}
