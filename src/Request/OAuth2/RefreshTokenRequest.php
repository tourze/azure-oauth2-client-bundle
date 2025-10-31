<?php

declare(strict_types=1);

namespace Tourze\AzureOAuth2ClientBundle\Request\OAuth2;

use HttpClientBundle\Request\RequestInterface;
use Tourze\AzureOAuth2ClientBundle\Entity\AzureOAuth2Config;

class RefreshTokenRequest implements RequestInterface
{
    private AzureOAuth2Config $config;

    private string $refreshToken;

    public function setConfig(AzureOAuth2Config $config): void
    {
        $this->config = $config;
    }

    public function setRefreshToken(string $refreshToken): void
    {
        $this->refreshToken = $refreshToken;
    }

    public function getRefreshToken(): string
    {
        return $this->refreshToken;
    }

    public function getConfig(): AzureOAuth2Config
    {
        return $this->config;
    }

    public function getRequestPath(): string
    {
        return sprintf('/%s/oauth2/v2.0/token', $this->config->getTenantId());
    }

    public function getRequestMethod(): ?string
    {
        return 'POST';
    }

    /**
     * @return array<string, mixed>
     */
    public function getRequestOptions(): array
    {
        $data = [
            'client_id' => $this->config->getClientId(),
            'client_secret' => $this->config->getClientSecret(),
            'refresh_token' => $this->refreshToken,
            'grant_type' => 'refresh_token',
        ];

        $scope = $this->config->getScope();
        if (null !== $scope) {
            $data['scope'] = $scope;
        }

        return [
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
            'body' => http_build_query($data),
        ];
    }
}
