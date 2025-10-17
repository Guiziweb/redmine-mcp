<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

/**
 * Authenticates users via JWT tokens in the Authorization header.
 * Validates the token and loads the user's Redmine credentials.
 */
final class JwtAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private readonly JwtTokenValidator $tokenValidator,
    ) {
    }

    public function supports(Request $request): ?bool
    {
        // Only authenticate if Authorization header is present
        return $request->headers->has('Authorization');
    }

    public function authenticate(Request $request): Passport
    {
        $authHeader = $request->headers->get('Authorization');

        if (!str_starts_with($authHeader, 'Bearer ')) {
            throw new AuthenticationException('Missing or invalid Authorization header');
        }

        $token = substr($authHeader, 7);

        try {
            $userId = $this->tokenValidator->validateAndGetUserId($token);
        } catch (\RuntimeException $e) {
            throw new AuthenticationException($e->getMessage(), 0, $e);
        }

        // UserBadge will load the user via the UserProvider
        return new SelfValidatingPassport(new UserBadge($userId));
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // Let the request continue to the controller
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        // Use X-Forwarded-Proto header from ngrok/reverse proxy
        $scheme = $request->headers->get('X-Forwarded-Proto', $request->getScheme());
        $host = $request->getHost();
        $baseUrl = $scheme.'://'.$host;

        return new JsonResponse(
            ['error' => 'unauthorized', 'message' => $exception->getMessage()],
            401,
            [
                'WWW-Authenticate' => sprintf(
                    'Bearer realm="MCP Redmine", resource_metadata="%s/.well-known/oauth-protected-resource"',
                    $baseUrl
                ),
            ]
        );
    }
}
