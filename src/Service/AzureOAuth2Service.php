<?php

declare(strict_types=1);

namespace Tourze\AzureOAuth2ClientBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use HttpClientBundle\Client\ApiClient;
use HttpClientBundle\Request\RequestInterface;
use HttpClientBundle\Service\SmartHttpClient;
use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Tourze\AzureOAuth2ClientBundle\Entity\AzureOAuth2Config;
use Tourze\AzureOAuth2ClientBundle\Entity\AzureOAuth2State;
use Tourze\AzureOAuth2ClientBundle\Entity\AzureOAuth2User;
use Tourze\AzureOAuth2ClientBundle\Exception\AzureOAuth2ApiException;
use Tourze\AzureOAuth2ClientBundle\Exception\AzureOAuth2ConfigurationException;
use Tourze\AzureOAuth2ClientBundle\Exception\AzureOAuth2Exception;
use Tourze\AzureOAuth2ClientBundle\Exception\AzureOAuth2RuntimeException;
use Tourze\AzureOAuth2ClientBundle\Repository\AzureOAuth2ConfigRepository;
use Tourze\AzureOAuth2ClientBundle\Repository\AzureOAuth2StateRepository;
use Tourze\AzureOAuth2ClientBundle\Repository\AzureOAuth2UserRepository;
use Tourze\AzureOAuth2ClientBundle\Request\OAuth2\GetAccessTokenRequest;
use Tourze\AzureOAuth2ClientBundle\Request\OAuth2\GetUserInfoRequest;
use Tourze\AzureOAuth2ClientBundle\Request\OAuth2\RefreshTokenRequest;
use Tourze\DoctrineAsyncInsertBundle\Service\AsyncInsertService;

#[WithMonologChannel(channel: 'azure_oauth2_client')]
class AzureOAuth2Service extends ApiClient
{
    private const GRAPH_BASE_URL = 'https://graph.microsoft.com';
    private const LOGIN_BASE_URL = 'https://login.microsoftonline.com';

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly SmartHttpClient $httpClient,
        private readonly AzureOAuth2ConfigRepository $configRepository,
        private readonly AzureOAuth2StateRepository $stateRepository,
        private readonly AzureOAuth2UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly LockFactory $lockFactory,
        private readonly CacheInterface $cache,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly AsyncInsertService $asyncInsertService,
    ) {
    }

    protected function getLockFactory(): LockFactory
    {
        return $this->lockFactory;
    }

    protected function getHttpClient(): SmartHttpClient
    {
        return $this->httpClient;
    }

    protected function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    protected function getCache(): CacheInterface
    {
        return $this->cache;
    }

    protected function getEventDispatcher(): EventDispatcherInterface
    {
        return $this->eventDispatcher;
    }

    protected function getAsyncInsertService(): AsyncInsertService
    {
        return $this->asyncInsertService;
    }

    public function generateAuthorizationUrl(?string $sessionId = null, ?string $codeChallenge = null, ?string $codeChallengeMethod = null, ?string $tenantId = null): string
    {
        $config = $this->getValidConfig($tenantId);
        $stateEntity = $this->createStateEntity($config, $sessionId, $codeChallenge, $codeChallengeMethod);

        $this->persistStateEntity($stateEntity);

        $redirectUri = $this->getRedirectUri($config);
        $params = $this->buildAuthorizationParams($config, $stateEntity->getState(), $redirectUri, $codeChallenge, $codeChallengeMethod);

        $authorizeUrl = sprintf('%s/%s/oauth2/v2.0/authorize', self::LOGIN_BASE_URL, $config->getTenantId());

        return $authorizeUrl . '?' . http_build_query($params);
    }

    public function handleCallback(string $code, string $state): AzureOAuth2User
    {
        $stateEntity = $this->stateRepository->findValidState($state);
        if (null === $stateEntity || !$stateEntity->isValid()) {
            throw new AzureOAuth2RuntimeException('Invalid or expired state');
        }

        $stateEntity->setUsed(true);
        $stateEntity->setUpdateTime(new \DateTimeImmutable());
        $this->entityManager->persist($stateEntity);
        $this->entityManager->flush();

        $config = $stateEntity->getConfig();
        $redirectUri = $config->getRedirectUri()
            ?? $this->urlGenerator->generate('azure_oauth2_callback', [], UrlGeneratorInterface::ABSOLUTE_URL);

        $tokenData = $this->getAccessToken($code, $config, $redirectUri, $stateEntity->getCodeChallenge());

        if (!isset($tokenData['access_token']) || !is_string($tokenData['access_token'])) {
            throw new AzureOAuth2ApiException('Invalid access token in response');
        }

        $userInfo = $this->getUserInfo($tokenData['access_token'], $config);

        $userData = array_merge($tokenData, $userInfo);

        $user = $this->userRepository->updateOrCreate($userData, $config);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    /**
     * @return array<string, mixed>
     */
    private function getAccessToken(string $code, AzureOAuth2Config $config, string $redirectUri, ?string $codeVerifier = null): array
    {
        $request = new GetAccessTokenRequest();
        $request->setConfig($config);
        $request->setCode($code);
        $request->setRedirectUri($redirectUri);

        if (null !== $codeVerifier) {
            $request->setCodeVerifier($codeVerifier);
        }

        $data = $this->requestWithBaseUrl($request, self::LOGIN_BASE_URL);

        if (!isset($data['access_token'])) {
            throw new AzureOAuth2ApiException('No access token received');
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function getUserInfo(string $accessToken, AzureOAuth2Config $config): array
    {
        $request = new GetUserInfoRequest();
        $request->setAccessToken($accessToken);

        return $this->requestWithBaseUrl($request, self::GRAPH_BASE_URL);
    }

    public function refreshExpiredTokens(): int
    {
        $expiredUsers = $this->userRepository->findExpiredTokenUsers();
        $refreshed = 0;

        foreach ($expiredUsers as $user) {
            if ($this->refreshToken($user->getObjectId())) {
                ++$refreshed;
            }

            usleep(100000); // 0.1秒
        }

        return $refreshed;
    }

    public function refreshToken(string $objectId): bool
    {
        $user = $this->userRepository->findByObjectId($objectId);
        if (null === $user) {
            return false;
        }

        if (null === $user->getRefreshToken()) {
            return false;
        }

        try {
            $data = $this->requestTokenRefresh($user);
            $this->updateUserTokens($user, $data);

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            return true;
        } catch (\Exception $e) {
            $this->logger->error('Failed to refresh Azure OAuth2 token', [
                'object_id' => $objectId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function requestTokenRefresh(AzureOAuth2User $user): array
    {
        $request = new RefreshTokenRequest();
        $request->setConfig($user->getConfig());
        $request->setRefreshToken((string) $user->getRefreshToken());

        return $this->requestWithBaseUrl($request, self::LOGIN_BASE_URL);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function updateUserTokens(AzureOAuth2User $user, array $data): void
    {
        $this->updateAccessToken($user, $data);
        $this->updateExpiresIn($user, $data);
        $this->updateRefreshToken($user, $data);
        $this->updateIdToken($user, $data);
        $this->updateScope($user, $data);

        $user->setUpdateTime(new \DateTimeImmutable());
    }

    public function cleanupExpiredStates(): int
    {
        return $this->stateRepository->cleanupExpiredStates();
    }

    /**
     * @return array<string, mixed>
     */
    public function fetchUserInfo(string $objectId, bool $forceRefresh = false): array
    {
        $user = $this->userRepository->findByObjectId($objectId);
        if (null === $user) {
            throw new AzureOAuth2RuntimeException('User not found');
        }

        if (!$forceRefresh && !$user->isTokenExpired() && null !== $user->getRawData()) {
            return $user->getRawData();
        }

        if ($user->isTokenExpired() && null !== $user->getRefreshToken()) {
            $this->refreshToken($objectId);
            $user = $this->userRepository->findByObjectId($objectId);
        }

        if (null === $user) {
            throw new AzureOAuth2RuntimeException('User not found after token refresh');
        }

        $userInfo = $this->getUserInfo($user->getAccessToken(), $user->getConfig());

        $user = $this->userRepository->updateOrCreate($userInfo, $user->getConfig());
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $userInfo;
    }

    public function getBaseUrl(): string
    {
        return self::GRAPH_BASE_URL;
    }

    protected function getRequestUrl(RequestInterface $request): string
    {
        return $request->getRequestPath();
    }

    protected function getRequestMethod(RequestInterface $request): string
    {
        return $request->getRequestMethod() ?? 'GET';
    }

    /**
     * @return array<mixed>|null
     */
    protected function getRequestOptions(RequestInterface $request): ?array
    {
        return $request->getRequestOptions();
    }

    protected function formatResponse(RequestInterface $request, ResponseInterface $response): mixed
    {
        $content = $response->getContent();
        $data = json_decode($content, true);

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new AzureOAuth2ApiException('Failed to parse response: ' . json_last_error_msg());
        }

        if (is_array($data) && isset($data['error'])) {
            $errorDescription = $data['error_description'] ?? 'Unknown error';
            $error = is_string($data['error']) ? $data['error'] : 'unknown';
            $errorDescriptionStr = is_string($errorDescription) ? $errorDescription : 'Unknown error';
            throw new AzureOAuth2ApiException(sprintf('Azure OAuth2 error: %s - %s', $error, $errorDescriptionStr));
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function requestWithBaseUrl(RequestInterface $request, string $baseUrl): array
    {
        $originalClient = $this->getHttpClient();

        // Create HTTP options with custom base URI
        $options = $request->getRequestOptions() ?? [];
        $options['base_uri'] = $baseUrl;

        $response = $originalClient->request(
            $this->getRequestMethod($request),
            $baseUrl . $this->getRequestUrl($request),
            $options
        );

        $result = $this->formatResponse($request, $response);

        /** @var array<string, mixed> */
        return is_array($result) ? $result : [];
    }

    private function getValidConfig(?string $tenantId): AzureOAuth2Config
    {
        $config = null !== $tenantId
            ? $this->configRepository->findByTenantId($tenantId)
            : $this->configRepository->findValidConfig();

        if (null === $config) {
            throw new AzureOAuth2ConfigurationException('No valid Azure OAuth2 configuration found');
        }

        return $config;
    }

    private function createStateEntity(AzureOAuth2Config $config, ?string $sessionId, ?string $codeChallenge, ?string $codeChallengeMethod): AzureOAuth2State
    {
        $state = bin2hex(random_bytes(16));
        $stateEntity = new AzureOAuth2State();
        $stateEntity->setState($state);
        $stateEntity->setConfig($config);

        if (null !== $sessionId) {
            $stateEntity->setSessionId($sessionId);
        }

        $this->setPkceParameters($stateEntity, $codeChallenge, $codeChallengeMethod);

        return $stateEntity;
    }

    private function setPkceParameters(AzureOAuth2State $stateEntity, ?string $codeChallenge, ?string $codeChallengeMethod): void
    {
        if (null === $codeChallenge) {
            return;
        }

        $stateEntity->setCodeChallenge($codeChallenge);
        if (null !== $codeChallengeMethod) {
            $stateEntity->setCodeChallengeMethod($codeChallengeMethod);
        }
    }

    private function persistStateEntity(AzureOAuth2State $stateEntity): void
    {
        $this->entityManager->persist($stateEntity);
        $this->entityManager->flush();
    }

    private function getRedirectUri(AzureOAuth2Config $config): string
    {
        return $config->getRedirectUri()
            ?? $this->urlGenerator->generate('azure_oauth2_callback', [], UrlGeneratorInterface::ABSOLUTE_URL);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildAuthorizationParams(AzureOAuth2Config $config, string $state, string $redirectUri, ?string $codeChallenge, ?string $codeChallengeMethod): array
    {
        $params = [
            'client_id' => $config->getClientId(),
            'response_type' => 'code',
            'redirect_uri' => $redirectUri,
            'state' => $state,
            'response_mode' => 'query',
            'scope' => $this->getScope($config),
        ];

        return $this->addPkceParams($params, $codeChallenge, $codeChallengeMethod);
    }

    private function getScope(AzureOAuth2Config $config): string
    {
        return $config->getScope() ?? 'openid profile email User.Read';
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    private function addPkceParams(array $params, ?string $codeChallenge, ?string $codeChallengeMethod): array
    {
        if (null === $codeChallenge) {
            return $params;
        }

        $params['code_challenge'] = $codeChallenge;
        if (null !== $codeChallengeMethod) {
            $params['code_challenge_method'] = $codeChallengeMethod;
        }

        return $params;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function updateAccessToken(AzureOAuth2User $user, array $data): void
    {
        if (isset($data['access_token']) && is_string($data['access_token'])) {
            $user->setAccessToken($data['access_token']);
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function updateExpiresIn(AzureOAuth2User $user, array $data): void
    {
        if (isset($data['expires_in']) && is_numeric($data['expires_in'])) {
            $user->setExpiresIn((int) $data['expires_in']);
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function updateRefreshToken(AzureOAuth2User $user, array $data): void
    {
        if (array_key_exists('refresh_token', $data)) {
            if (is_string($data['refresh_token']) || is_null($data['refresh_token'])) {
                $user->setRefreshToken($data['refresh_token']);
            }
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function updateIdToken(AzureOAuth2User $user, array $data): void
    {
        if (array_key_exists('id_token', $data)) {
            if (is_string($data['id_token']) || is_null($data['id_token'])) {
                $user->setIdToken($data['id_token']);
            }
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function updateScope(AzureOAuth2User $user, array $data): void
    {
        if (array_key_exists('scope', $data)) {
            if (is_string($data['scope']) || is_null($data['scope'])) {
                $user->setScope($data['scope']);
            }
        }
    }
}
