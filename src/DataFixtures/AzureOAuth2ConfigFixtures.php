<?php

declare(strict_types=1);

namespace Tourze\AzureOAuth2ClientBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Tourze\AzureOAuth2ClientBundle\Entity\AzureOAuth2Config;

class AzureOAuth2ConfigFixtures extends Fixture
{
    public const AZURE_OAUTH2_CONFIG_REFERENCE = 'azure-oauth2-config';
    public const AZURE_OAUTH2_INVALID_CONFIG_REFERENCE = 'azure-oauth2-invalid-config';

    public function load(ObjectManager $manager): void
    {
        // Create test config
        $config = new AzureOAuth2Config();
        $config->setClientId('test-client-id-fixture');
        $config->setClientSecret('test-client-secret-fixture');
        $config->setTenantId('test-tenant-id-fixture');
        $config->setName('Test Config from Fixture');
        $config->setScope('openid profile email');
        $config->setRedirectUri('https://localhost:8000/auth/callback');
        $config->setRemark('Test configuration created by fixture');
        $config->setValid(true);

        $manager->persist($config);

        // Create invalid config for testing
        $invalidConfig = new AzureOAuth2Config();
        $invalidConfig->setClientId('invalid-client-id-fixture');
        $invalidConfig->setClientSecret('invalid-client-secret-fixture');
        $invalidConfig->setTenantId('invalid-tenant-id-fixture');
        $invalidConfig->setName('Invalid Config from Fixture');
        $invalidConfig->setScope('openid profile');
        $invalidConfig->setRedirectUri('https://localhost:8000/invalid/callback');
        $invalidConfig->setRemark('Invalid test configuration');
        $invalidConfig->setValid(false);

        $manager->persist($invalidConfig);

        // Add reference for other fixtures to use
        $this->addReference(self::AZURE_OAUTH2_CONFIG_REFERENCE, $config);
        $this->addReference(self::AZURE_OAUTH2_INVALID_CONFIG_REFERENCE, $invalidConfig);

        $manager->flush();
    }
}
