<?php

namespace App\Service;

use Auth0\SDK\Auth0;
use Auth0\SDK\Configuration\SdkConfiguration;

class Auth0JwtValidator
{
    private Auth0 $auth0;

    public function __construct(
        private readonly string $auth0Domain,
        private readonly string $auth0Audience,
    ) {
        $config = new SdkConfiguration(
            strategy: SdkConfiguration::STRATEGY_API,
            domain: $this->auth0Domain,
            audience: [$this->auth0Audience]
        );

        $this->auth0 = new Auth0($config);
    }

    public function validate(string $token): array
    {
        // Decoder et valider le token avec le SDK Auth0
        $decoded = $this->auth0->decode($token);

        if ($decoded === null) {
            throw new \Exception('Invalid or expired token');
        }

        return $decoded->toArray();
    }
}