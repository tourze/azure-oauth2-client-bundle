<?php

declare(strict_types=1);

namespace Tourze\AzureOAuth2ClientBundle\Tests\Request\OAuth2;

use HttpClientBundle\Test\RequestTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\AzureOAuth2ClientBundle\Request\OAuth2\GetUserInfoRequest;

/**
 * @internal
 */
#[CoversClass(GetUserInfoRequest::class)]
class GetUserInfoRequestTest extends RequestTestCase
{
    public function testRequestConfiguration(): void
    {
        $request = new GetUserInfoRequest();
        $request->setAccessToken('access-token-123');

        $this->assertSame('GET', $request->getRequestMethod());
        $this->assertSame('/v1.0/me', $request->getRequestPath());

        $options = $request->getRequestOptions();
        $this->assertArrayHasKey('headers', $options);
        // Type safety: assert that headers is an array and contains Authorization
        $this->assertIsArray($options['headers']);
        $this->assertArrayHasKey('Authorization', $options['headers']);
        $this->assertSame('Bearer access-token-123', $options['headers']['Authorization']);
    }

    public function testGetAndSetAccessToken(): void
    {
        $request = new GetUserInfoRequest();

        $request->setAccessToken('test-token');
        $this->assertSame('test-token', $request->getAccessToken());
    }
}
