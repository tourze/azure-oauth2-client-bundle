<?php

declare(strict_types=1);

namespace Tourze\AzureOAuth2ClientBundle\Tests\Request\OAuth2;

use HttpClientBundle\Tests\Request\RequestTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\AzureOAuth2ClientBundle\Entity\AzureOAuth2Config;
use Tourze\AzureOAuth2ClientBundle\Request\OAuth2\RefreshTokenRequest;

/**
 * @internal
 */
#[CoversClass(RefreshTokenRequest::class)]
class RefreshTokenRequestTest extends RequestTestCase
{
    public function testRequestConfiguration(): void
    {
        $config = new AzureOAuth2Config();
        $config->setClientId('test-client-id');
        $config->setClientSecret('test-client-secret');
        $config->setTenantId('test-tenant-id');

        $request = new RefreshTokenRequest();
        $request->setConfig($config);
        $request->setRefreshToken('refresh-token-123');

        $this->assertSame('POST', $request->getRequestMethod());
        $this->assertSame('/test-tenant-id/oauth2/v2.0/token', $request->getRequestPath());

        $options = $request->getRequestOptions();
        $this->assertArrayHasKey('headers', $options);
        $this->assertArrayHasKey('body', $options);

        // Type safety: assert that headers is an array and contains Content-Type
        $this->assertIsArray($options['headers']);
        $this->assertArrayHasKey('Content-Type', $options['headers']);
        // Type safety: assert that headers is an array and contains Content-Type
        $this->assertIsArray($options['headers']);
        $this->assertArrayHasKey('Content-Type', $options['headers']);
        $this->assertSame('application/x-www-form-urlencoded', $options['headers']['Content-Type']);

        // Type safety: assert that body is a string before parsing
        $this->assertIsString($options['body']);
        parse_str($options['body'], $formParams);
        $this->assertSame('test-client-id', $formParams['client_id']);
        $this->assertSame('test-client-secret', $formParams['client_secret']);
        $this->assertSame('refresh-token-123', $formParams['refresh_token']);
        $this->assertSame('refresh_token', $formParams['grant_type']);
    }

    public function testGetAndSetRefreshToken(): void
    {
        $request = new RefreshTokenRequest();

        $request->setRefreshToken('test-refresh-token');
        $this->assertSame('test-refresh-token', $request->getRefreshToken());
    }

    public function testGetAndSetConfig(): void
    {
        $config = new AzureOAuth2Config();
        $request = new RefreshTokenRequest();

        $request->setConfig($config);
        $this->assertSame($config, $request->getConfig());
    }
}
