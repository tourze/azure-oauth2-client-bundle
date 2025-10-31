<?php

declare(strict_types=1);

namespace Tourze\AzureOAuth2ClientBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Tourze\AzureOAuth2ClientBundle\Entity\AzureOAuth2Config;
use Tourze\AzureOAuth2ClientBundle\Entity\AzureOAuth2User;

class AzureOAuth2UserFixtures extends Fixture implements DependentFixtureInterface
{
    public const AZURE_OAUTH2_USER_ACTIVE_REFERENCE = 'azure-oauth2-user-active';
    public const AZURE_OAUTH2_USER_EXPIRED_REFERENCE = 'azure-oauth2-user-expired';
    public const AZURE_OAUTH2_USER_EXPIRED_NO_REFRESH_REFERENCE = 'azure-oauth2-user-expired-no-refresh';

    public function load(ObjectManager $manager): void
    {
        /** @var AzureOAuth2Config $config */
        $config = $this->getReference(AzureOAuth2ConfigFixtures::AZURE_OAUTH2_CONFIG_REFERENCE, AzureOAuth2Config::class);

        // Create active user with valid token
        $activeUser = new AzureOAuth2User();
        $activeUser->setConfig($config);
        $activeUser->setObjectId('user-active-fixture');
        $activeUser->setUserPrincipalName('active@fixture.com');
        $activeUser->setDisplayName('Active Test User');
        $activeUser->setGivenName('Active');
        $activeUser->setSurname('User');
        $activeUser->setMail('active@fixture.com');
        $activeUser->setMobilePhone('+1234567890');
        $activeUser->setOfficeLocation('Building 1 - Fixture');
        $activeUser->setPreferredLanguage('en-US');
        $activeUser->setJobTitle('Test Developer');
        $activeUser->setAccessToken('active-access-token-fixture');
        $activeUser->setRefreshToken('active-refresh-token-fixture');
        $activeUser->setIdToken('active-id-token-fixture');
        $activeUser->setExpiresIn(3600);
        $activeUser->setScope('openid profile email');
        $activeUser->setRawData(['fixture' => 'active-user-data']);

        $manager->persist($activeUser);

        // Create user with expired token but refresh token available
        $expiredUser = new AzureOAuth2User();
        $expiredUser->setConfig($config);
        $expiredUser->setObjectId('user-expired-fixture');
        $expiredUser->setUserPrincipalName('expired@fixture.com');
        $expiredUser->setDisplayName('Expired Test User');
        $expiredUser->setGivenName('Expired');
        $expiredUser->setSurname('User');
        $expiredUser->setMail('expired@fixture.com');
        $expiredUser->setJobTitle('Expired Developer');
        $expiredUser->setAccessToken('expired-access-token-fixture');
        $expiredUser->setRefreshToken('expired-refresh-token-fixture');
        $expiredUser->setIdToken('expired-id-token-fixture');
        $expiredUser->setExpiresIn(-3600); // Expired 1 hour ago
        $expiredUser->setScope('openid profile');
        $expiredUser->setRawData(['fixture' => 'expired-user-data']);

        $manager->persist($expiredUser);

        // Create user with expired token but no refresh token
        $expiredNoRefreshUser = new AzureOAuth2User();
        $expiredNoRefreshUser->setConfig($config);
        $expiredNoRefreshUser->setObjectId('user-expired-no-refresh-fixture');
        $expiredNoRefreshUser->setUserPrincipalName('expired-no-refresh@fixture.com');
        $expiredNoRefreshUser->setDisplayName('Expired No Refresh User');
        $expiredNoRefreshUser->setAccessToken('expired-no-refresh-access-token');
        $expiredNoRefreshUser->setExpiresIn(-7200); // Expired 2 hours ago
        $expiredNoRefreshUser->setScope('openid');
        $expiredNoRefreshUser->setRawData(['fixture' => 'expired-no-refresh-data']);

        $manager->persist($expiredNoRefreshUser);

        $this->addReference(self::AZURE_OAUTH2_USER_ACTIVE_REFERENCE, $activeUser);
        $this->addReference(self::AZURE_OAUTH2_USER_EXPIRED_REFERENCE, $expiredUser);
        $this->addReference(self::AZURE_OAUTH2_USER_EXPIRED_NO_REFRESH_REFERENCE, $expiredNoRefreshUser);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            AzureOAuth2ConfigFixtures::class,
        ];
    }
}
