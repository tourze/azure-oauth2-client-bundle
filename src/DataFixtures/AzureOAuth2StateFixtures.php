<?php

declare(strict_types=1);

namespace Tourze\AzureOAuth2ClientBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Tourze\AzureOAuth2ClientBundle\Entity\AzureOAuth2Config;
use Tourze\AzureOAuth2ClientBundle\Entity\AzureOAuth2State;

class AzureOAuth2StateFixtures extends Fixture implements DependentFixtureInterface
{
    public const AZURE_OAUTH2_STATE_ACTIVE_REFERENCE = 'azure-oauth2-state-active';
    public const AZURE_OAUTH2_STATE_USED_REFERENCE = 'azure-oauth2-state-used';
    public const AZURE_OAUTH2_STATE_EXPIRED_REFERENCE = 'azure-oauth2-state-expired';

    public function load(ObjectManager $manager): void
    {
        /** @var AzureOAuth2Config $config */
        $config = $this->getReference(AzureOAuth2ConfigFixtures::AZURE_OAUTH2_CONFIG_REFERENCE, AzureOAuth2Config::class);

        // Create active state
        $activeState = new AzureOAuth2State();
        $activeState->setState('active-state-fixture');
        $activeState->setConfig($config);
        $activeState->setSessionId('test-session-active');
        $activeState->setCodeChallenge('test-code-challenge-active');
        $activeState->setCodeChallengeMethod('S256');
        $activeState->setUsed(false);
        // Default expires time is already set in constructor

        $manager->persist($activeState);

        // Create used state
        $usedState = new AzureOAuth2State();
        $usedState->setState('used-state-fixture');
        $usedState->setConfig($config);
        $usedState->setSessionId('test-session-used');
        $usedState->setCodeChallenge('test-code-challenge-used');
        $usedState->setCodeChallengeMethod('S256');
        $usedState->setUsed(true);

        $manager->persist($usedState);

        // Create expired state
        $expiredState = new AzureOAuth2State();
        $expiredState->setState('expired-state-fixture');
        $expiredState->setConfig($config);
        $expiredState->setSessionId('test-session-expired');
        $expiredState->setCodeChallenge('test-code-challenge-expired');
        $expiredState->setCodeChallengeMethod('S256');
        $expiredState->setUsed(false);
        $expiredState->setExpiresTime(new \DateTimeImmutable('-1 hour'));

        $manager->persist($expiredState);

        $this->addReference(self::AZURE_OAUTH2_STATE_ACTIVE_REFERENCE, $activeState);
        $this->addReference(self::AZURE_OAUTH2_STATE_USED_REFERENCE, $usedState);
        $this->addReference(self::AZURE_OAUTH2_STATE_EXPIRED_REFERENCE, $expiredState);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            AzureOAuth2ConfigFixtures::class,
        ];
    }
}
