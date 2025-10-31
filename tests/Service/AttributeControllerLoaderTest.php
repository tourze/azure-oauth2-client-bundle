<?php

declare(strict_types=1);

namespace Tourze\AzureOAuth2ClientBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Routing\RouteCollection;
use Tourze\AzureOAuth2ClientBundle\Service\AttributeControllerLoader;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(AttributeControllerLoader::class)]
#[RunTestsInSeparateProcesses]
class AttributeControllerLoaderTest extends AbstractIntegrationTestCase
{
    private AttributeControllerLoader $loader;

    protected function onSetUp(): void
    {
        $this->loader = self::getService(AttributeControllerLoader::class);
    }

    public function testLoad(): void
    {
        $collection = $this->loader->load('test');

        $this->assertInstanceOf(RouteCollection::class, $collection);
        $this->assertGreaterThan(0, $collection->count());
    }

    public function testAutoload(): void
    {
        $collection = $this->loader->autoload();

        $this->assertInstanceOf(RouteCollection::class, $collection);
        $this->assertGreaterThan(0, $collection->count());
    }

    public function testSupports(): void
    {
        $this->assertFalse($this->loader->supports('test'));
        $this->assertFalse($this->loader->supports('test', 'annotation'));
    }

    public function testLoadAndAutoloadReturnSameCollection(): void
    {
        $loadCollection = $this->loader->load('test');
        $autoloadCollection = $this->loader->autoload();

        $this->assertEquals($loadCollection->count(), $autoloadCollection->count());
    }
}
