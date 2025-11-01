<?php

declare(strict_types=1);

namespace Tourze\AzureOAuth2ClientBundle\Tests\Request\OAuth2;

use HttpClientBundle\Test\RequestTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\AzureOAuth2ClientBundle\Entity\AzureOAuth2Config;
use Tourze\AzureOAuth2ClientBundle\Request\OAuth2\GetAccessTokenRequest;

/**
 * @internal
 */
#[CoversClass(GetAccessTokenRequest::class)]
class GetAccessTokenRequestTest extends RequestTestCase
{
    public function testRequestConfiguration(): void
    {
        $config = new AzureOAuth2Config();
        $config->setClientId('test-client-id');
        $config->setClientSecret('test-client-secret');
        $config->setTenantId('test-tenant-id');

        $request = new GetAccessTokenRequest();
        $request->setConfig($config);
        $request->setCode('auth-code');
        $request->setRedirectUri('https://example.com/callback');

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
        $this->assertSame('auth-code', $formParams['code']);
        $this->assertSame('authorization_code', $formParams['grant_type']);
        $this->assertSame('https://example.com/callback', $formParams['redirect_uri']);
    }

    public function testRequestWithCodeVerifier(): void
    {
        $config = new AzureOAuth2Config();
        $config->setClientId('test-client-id');
        $config->setClientSecret('test-client-secret');
        $config->setTenantId('test-tenant-id');

        $request = new GetAccessTokenRequest();
        $request->setConfig($config);
        $request->setCode('auth-code');
        $request->setRedirectUri('https://example.com/callback');
        $request->setCodeVerifier('code-verifier');

        $options = $request->getRequestOptions();
        // Type safety: assert that body is a string before parsing
        $this->assertIsString($options['body']);
        parse_str($options['body'], $formParams);

        $this->assertSame('code-verifier', $formParams['code_verifier']);
    }
}
