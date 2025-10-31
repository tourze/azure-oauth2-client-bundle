<?php

declare(strict_types=1);

namespace Tourze\AzureOAuth2ClientBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Tourze\AzureOAuth2ClientBundle\Service\AzureOAuth2Service;

final class AzureOAuth2LoginController extends AbstractController
{
    public function __construct(
        private readonly AzureOAuth2Service $azureOAuth2Service,
    ) {
    }

    #[Route(path: '/azure/oauth2/login', name: 'azure_oauth2_login', methods: ['GET'])]
    public function __invoke(Request $request): RedirectResponse
    {
        $sessionId = $request->getSession()->getId();
        $codeChallenge = $request->query->get('code_challenge');
        $codeChallengeMethod = $request->query->get('code_challenge_method');
        $tenantId = $request->query->get('tenant_id');

        $codeChallenge = is_string($codeChallenge) ? $codeChallenge : null;
        $codeChallengeMethod = is_string($codeChallengeMethod) ? $codeChallengeMethod : null;
        $tenantId = is_string($tenantId) ? $tenantId : null;

        $authorizationUrl = $this->azureOAuth2Service->generateAuthorizationUrl(
            $sessionId,
            $codeChallenge,
            $codeChallengeMethod,
            $tenantId
        );

        return new RedirectResponse($authorizationUrl);
    }
}
