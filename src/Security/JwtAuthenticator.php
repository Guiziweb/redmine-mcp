<?php

namespace App\Security;

use App\Exception\InvalidTokenException;
use App\Service\KeycloakJwtValidator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class JwtAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private readonly KeycloakJwtValidator $jwtValidator,
    ) {
    }

    public function supports(Request $request): ?bool
    {
        return $request->headers->has('Authorization');
    }

    public function authenticate(Request $request): Passport
    {
        $authHeader = $request->headers->get('Authorization');

        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            throw new AuthenticationException('Missing or invalid Authorization header');
        }

        $token = substr($authHeader, 7);

        try {
            $payload = $this->jwtValidator->validate($token);
        } catch (InvalidTokenException $e) {
            throw new AuthenticationException($e->getMessage(), 0, $e);
        }

        $userIdentifier = $payload['sub'] ?? $payload['email'] ?? 'unknown';

        return new SelfValidatingPassport(
            new UserBadge($userIdentifier, function (string $identifier) use ($payload) {
                return new JwtUser(
                    identifier: $identifier,
                    roles: ['ROLE_USER'],
                    claims: $payload
                );
            })
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // Let the request continue
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse(
            ['error' => $exception->getMessage()],
            Response::HTTP_UNAUTHORIZED,
            ['WWW-Authenticate' => 'Bearer realm="MCP Server"']
        );
    }
}
