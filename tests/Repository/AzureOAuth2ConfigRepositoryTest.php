<?php

declare(strict_types=1);

namespace Tourze\AzureOAuth2ClientBundle\Tests\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\AzureOAuth2ClientBundle\Entity\AzureOAuth2Config;
use Tourze\AzureOAuth2ClientBundle\Repository\AzureOAuth2ConfigRepository;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;

/**
 * @internal
 */
#[CoversClass(AzureOAuth2ConfigRepository::class)]
#[RunTestsInSeparateProcesses]
class AzureOAuth2ConfigRepositoryTest extends AbstractRepositoryTestCase
{
    /**
     * @return ServiceEntityRepository<AzureOAuth2Config>
     */
    protected function getRepository(): ServiceEntityRepository
    {
        return self::getService(AzureOAuth2ConfigRepository::class);
    }

    protected function createNewEntity(): AzureOAuth2Config
    {
        $config = new AzureOAuth2Config();
        $config->setClientId('test-client-' . uniqid());
        $config->setClientSecret('test-secret-' . uniqid());
        $config->setTenantId('test-tenant-' . uniqid());
        $config->setValid(true);

        return $config;
    }

    protected function onSetUp(): void
    {
        // Create initial data to satisfy testCountWithDataFixtureShouldReturnGreaterThanZero
        $config = $this->createNewEntity();
        $config->setName('Setup Config');
        $config->setRemark('Created during test setup');
        /** @var AzureOAuth2ConfigRepository $repository */
        $repository = $this->getRepository();
        $repository->save($config, true);
    }

    public function testFindValidConfig(): void
    {
        /** @var AzureOAuth2ConfigRepository $repository */
        $repository = $this->getRepository();

        // Create a valid config
        $config = $this->createNewEntity();
        $repository->save($config, true);

        $result = $repository->findValidConfig();

        $this->assertInstanceOf(AzureOAuth2Config::class, $result);
        $this->assertTrue($result->isValid());
    }

    public function testFindValidConfigReturnsNull(): void
    {
        /** @var AzureOAuth2ConfigRepository $repository */
        $repository = $this->getRepository();

        // Clear all valid configs
        foreach ($repository->findAllValid() as $config) {
            $config->setValid(false);
            $repository->save($config, true);
        }

        $result = $repository->findValidConfig();

        $this->assertNull($result);
    }

    public function testFindByClientId(): void
    {
        /** @var AzureOAuth2ConfigRepository $repository */
        $repository = $this->getRepository();

        $config = $this->createNewEntity();
        $clientId = $config->getClientId();
        $repository->save($config, true);

        $result = $repository->findByClientId($clientId);

        $this->assertInstanceOf(AzureOAuth2Config::class, $result);
        $this->assertSame($clientId, $result->getClientId());
    }

    public function testFindByTenantId(): void
    {
        /** @var AzureOAuth2ConfigRepository $repository */
        $repository = $this->getRepository();

        $config = $this->createNewEntity();
        $tenantId = $config->getTenantId();
        $repository->save($config, true);

        $result = $repository->findByTenantId($tenantId);

        $this->assertInstanceOf(AzureOAuth2Config::class, $result);
        $this->assertSame($tenantId, $result->getTenantId());
        $this->assertTrue($result->isValid());
    }

    public function testFindAllValid(): void
    {
        /** @var AzureOAuth2ConfigRepository $repository */
        $repository = $this->getRepository();

        // Create multiple valid configs
        $config1 = $this->createNewEntity();
        $config2 = $this->createNewEntity();
        $repository->save($config1, true);
        $repository->save($config2, true);

        $result = $repository->findAllValid();

        $this->assertIsArray($result);
        $this->assertContainsOnlyInstancesOf(AzureOAuth2Config::class, $result);
        foreach ($result as $config) {
            $this->assertTrue($config->isValid());
        }
    }
}
