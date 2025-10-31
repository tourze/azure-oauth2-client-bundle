<?php

declare(strict_types=1);

namespace Tourze\AzureOAuth2ClientBundle\Request\OAuth2;

use HttpClientBundle\Request\RequestInterface;

class GetUserInfoRequest implements RequestInterface
{
    private string $accessToken;

    public function setAccessToken(string $accessToken): void
    {
        $this->accessToken = $accessToken;
    }

    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

    public function getRequestPath(): string
    {
        return '/v1.0/me';
    }

    public function getRequestMethod(): ?string
    {
        return 'GET';
    }

    /**
     * @return array<string, mixed>
     */
    public function getRequestOptions(): array
    {
        return [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Accept' => 'application/json',
            ],
        ];
    }
}
