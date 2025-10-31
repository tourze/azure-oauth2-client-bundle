<?php

declare(strict_types=1);

namespace Tourze\AzureOAuth2ClientBundle\Tests\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\AzureOAuth2ClientBundle\Entity\AzureOAuth2Config;
use Tourze\AzureOAuth2ClientBundle\Entity\AzureOAuth2State;
use Tourze\AzureOAuth2ClientBundle\Repository\AzureOAuth2ConfigRepository;
use Tourze\AzureOAuth2ClientBundle\Repository\AzureOAuth2StateRepository;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;

/**
 * @internal
 */
#[CoversClass(AzureOAuth2StateRepository::class)]
#[RunTestsInSeparateProcesses]
class AzureOAuth2StateRepositoryTest extends AbstractRepositoryTestCase
{
    /**
     * @return ServiceEntityRepository<AzureOAuth2State>
     */
    protected function getRepository(): ServiceEntityRepository
    {
        return self::getService(AzureOAuth2StateRepository::class);
    }

    protected function createNewEntity(): AzureOAuth2State
    {
        $config = $this->createConfig();

        $state = new AzureOAuth2State();
        $state->setState('test-state-' . uniqid());
        $state->setConfig($config);

        return $state;
    }

    protected function onSetUp(): void
    {
        // Create initial data to satisfy testCountWithDataFixtureShouldReturnGreaterThanZero
        $config = $this->createConfig();
        $state = new AzureOAuth2State();
        $state->setState('setup-state-' . uniqid());
        $state->setConfig($config);
        $state->setSessionId('setup-session');
        /** @var AzureOAuth2StateRepository $repository */
        $repository = $this->getRepository();
        $repository->save($state, true);
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

    public function testFindValidState(): void
    {
        /** @var AzureOAuth2StateRepository $repository */
        $repository = $this->getRepository();

        // Create a valid state
        $state = $this->createNewEntity();
        $repository->save($state, true);

        $result = $repository->findValidState($state->getState());

        $this->assertInstanceOf(AzureOAuth2State::class, $result);
        $this->assertSame($state->getState(), $result->getState());
        $this->assertFalse($result->isUsed());
        $this->assertGreaterThan(new \DateTimeImmutable(), $result->getExpiresTime());
    }

    public function testFindValidStateReturnsNullWhenUsed(): void
    {
        /** @var AzureOAuth2StateRepository $repository */
        $repository = $this->getRepository();

        // Create a used state
        $state = $this->createNewEntity();
        $state->setUsed(true);
        $repository->save($state, true);

        $result = $repository->findValidState($state->getState());

        $this->assertNull($result);
    }

    public function testFindValidStateReturnsNullWhenExpired(): void
    {
        /** @var AzureOAuth2StateRepository $repository */
        $repository = $this->getRepository();

        // Create an expired state
        $state = $this->createNewEntity();
        $state->setExpiresTime(new \DateTimeImmutable('-1 hour'));
        $repository->save($state, true);

        $result = $repository->findValidState($state->getState());

        $this->assertNull($result);
    }

    public function testFindValidStateReturnsNullWhenNotFound(): void
    {
        /** @var AzureOAuth2StateRepository $repository */
        $repository = $this->getRepository();

        $result = $repository->findValidState('non-existent-state');

        $this->assertNull($result);
    }

    public function testCleanupExpiredStates(): void
    {
        /** @var AzureOAuth2StateRepository $repository */
        $repository = $this->getRepository();

        // Create expired and used states
        $expiredState = $this->createNewEntity();
        $expiredState->setExpiresTime(new \DateTimeImmutable('-1 hour'));
        $repository->save($expiredState, true);

        $usedState = $this->createNewEntity();
        $usedState->setUsed(true);
        $repository->save($usedState, true);

        // Create a valid state (should not be cleaned up)
        $validState = $this->createNewEntity();
        $repository->save($validState, true);

        // Get initial count
        $initialCount = count($repository->findAll());
        $this->assertGreaterThanOrEqual(3, $initialCount);

        // Run cleanup
        $deletedCount = $repository->cleanupExpiredStates();

        // Verify cleanup results
        $this->assertGreaterThanOrEqual(2, $deletedCount);

        // Verify valid state still exists
        $remainingStates = $repository->findAll();
        $this->assertContains($validState, $remainingStates);

        // Verify expired and used states are gone
        $this->assertNotContains($expiredState, $remainingStates);
        $this->assertNotContains($usedState, $remainingStates);
    }

    public function testSave(): void
    {
        /** @var AzureOAuth2StateRepository $repository */
        $repository = $this->getRepository();

        $state = $this->createNewEntity();

        // Test save without flush
        $repository->save($state, false);
        // ID won't be assigned until flush, so we manually flush to verify persistence worked
        self::getEntityManager()->flush();
        $this->assertNotNull($state->getId());

        // Test save with flush (default)
        $state2 = $this->createNewEntity();
        $repository->save($state2);
        $this->assertNotNull($state2->getId());

        // Verify both states are persisted
        $allStates = $repository->findAll();
        $this->assertContains($state, $allStates);
        $this->assertContains($state2, $allStates);
    }

    public function testRemove(): void
    {
        /** @var AzureOAuth2StateRepository $repository */
        $repository = $this->getRepository();

        $state = $this->createNewEntity();
        $repository->save($state, true);

        $stateId = $state->getId();
        $this->assertNotNull($stateId);

        // Test remove without flush
        $repository->remove($state, false);

        // State should still exist until flush
        $found = $repository->find($stateId);
        $this->assertNotNull($found);

        // Flush to complete removal
        self::getEntityManager()->flush();

        // Now state should be gone
        $found = $repository->find($stateId);
        $this->assertNull($found);
    }

    public function testRemoveWithDefaultFlush(): void
    {
        /** @var AzureOAuth2StateRepository $repository */
        $repository = $this->getRepository();

        $state = $this->createNewEntity();
        $repository->save($state, true);

        $stateId = $state->getId();
        $this->assertNotNull($stateId);

        // Test remove with flush (default)
        $repository->remove($state);

        // State should be immediately gone
        $found = $repository->find($stateId);
        $this->assertNull($found);
    }

    public function testStateEntityProperties(): void
    {
        /** @var AzureOAuth2StateRepository $repository */
        $repository = $this->getRepository();

        $config = $this->createConfig();
        $state = new AzureOAuth2State();
        $state->setState('test-state-123');
        $state->setConfig($config);
        $state->setSessionId('session-456');
        $state->setCodeChallenge('challenge-789');
        $state->setCodeChallengeMethod('S256');

        $repository->save($state, true);

        // Reload from database
        $reloadedState = $repository->find($state->getId());

        $this->assertInstanceOf(AzureOAuth2State::class, $reloadedState);
        $this->assertSame('test-state-123', $reloadedState->getState());
        $this->assertSame('session-456', $reloadedState->getSessionId());
        $this->assertSame('challenge-789', $reloadedState->getCodeChallenge());
        $this->assertSame('S256', $reloadedState->getCodeChallengeMethod());
        $this->assertFalse($reloadedState->isUsed());
        $this->assertTrue($reloadedState->isValid());
        $this->assertSame($config, $reloadedState->getConfig());
    }
}
