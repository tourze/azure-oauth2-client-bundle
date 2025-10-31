<?php

declare(strict_types=1);

namespace Tourze\AzureOAuth2ClientBundle\Request\OAuth2;

use HttpClientBundle\Request\RequestInterface;
use Tourze\AzureOAuth2ClientBundle\Entity\AzureOAuth2Config;

class GetAccessTokenRequest implements RequestInterface
{
    private AzureOAuth2Config $config;

    private string $code;

    private string $redirectUri;

    private ?string $codeVerifier = null;

    public function setConfig(AzureOAuth2Config $config): void
    {
        $this->config = $config;
    }

    public function setCode(string $code): void
    {
        $this->code = $code;
    }

    public function setRedirectUri(string $redirectUri): void
    {
        $this->redirectUri = $redirectUri;
    }

    public function setCodeVerifier(?string $codeVerifier): void
    {
        $this->codeVerifier = $codeVerifier;
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
            'code' => $this->code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $this->redirectUri,
        ];

        if (null !== $this->codeVerifier) {
            $data['code_verifier'] = $this->codeVerifier;
        }

        return [
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
            'body' => http_build_query($data),
        ];
    }
}
