<?php

declare(strict_types=1);

namespace Tourze\AzureOAuth2ClientBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Tourze\AzureOAuth2ClientBundle\Exception\AzureOAuth2Exception;
use Tourze\AzureOAuth2ClientBundle\Service\AzureOAuth2Service;

final class AzureOAuth2CallbackController extends AbstractController
{
    public function __construct(
        private readonly AzureOAuth2Service $azureOAuth2Service,
    ) {
    }

    #[Route(path: '/azure/oauth2/callback', name: 'azure_oauth2_callback', methods: ['GET'])]
    public function __invoke(Request $request): JsonResponse
    {
        $code = $request->query->get('code');
        $state = $request->query->get('state');

        $code = is_string($code) ? $code : null;
        $state = is_string($state) ? $state : null;
        $error = $request->query->get('error');

        if (null !== $error) {
            $errorDescription = $request->query->get('error_description', 'Unknown error');

            return new JsonResponse([
                'success' => false,
                'error' => $error,
                'error_description' => $errorDescription,
            ], Response::HTTP_BAD_REQUEST);
        }

        if (null === $code || null === $state) {
            return new JsonResponse([
                'success' => false,
                'error' => 'missing_parameters',
                'error_description' => 'Missing required parameters: code or state',
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $user = $this->azureOAuth2Service->handleCallback($code, $state);

            return new JsonResponse([
                'success' => true,
                'user' => [
                    'object_id' => $user->getObjectId(),
                    'user_principal_name' => $user->getUserPrincipalName(),
                    'display_name' => $user->getDisplayName(),
                    'mail' => $user->getMail(),
                ],
            ]);
        } catch (AzureOAuth2Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => 'oauth2_error',
                'error_description' => $e->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => 'internal_error',
                'error_description' => 'An internal error occurred',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
