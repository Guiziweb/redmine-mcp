<?php

namespace App\Service;

use App\Exception\InvalidTokenException;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;

class KeycloakJwtValidator
{
    public function __construct(
        private readonly string $keycloakUrl,
        private readonly string $keycloakRealm,
        private readonly string $keycloakAudience,
    ) {
    }

    /** @return array<string, mixed> */
    public function validate(string $token): array
    {
        try {
            $publicKeys = $this->getPublicKeys();
            $decoded = JWT::decode($token, $publicKeys);
            $payload = (array) $decoded;

            // Vérifier l'audience (peut être string ou array)
            $this->validateAudience($payload);

            return $payload;
        } catch (\Firebase\JWT\ExpiredException $e) {
            throw new InvalidTokenException('Token expired: '.$e->getMessage(), 0, $e);
        } catch (\Firebase\JWT\SignatureInvalidException $e) {
            throw new InvalidTokenException('Invalid signature: '.$e->getMessage(), 0, $e);
        } catch (InvalidTokenException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new InvalidTokenException('Invalid token: '.$e->getMessage(), 0, $e);
        }
    }

    /** @param array<string, mixed> $payload */
    private function validateAudience(array $payload): void
    {
        if (!isset($payload['aud'])) {
            throw new InvalidTokenException('Missing audience claim');
        }

        $audiences = is_array($payload['aud']) ? $payload['aud'] : [$payload['aud']];

        if (!in_array($this->keycloakAudience, $audiences, true)) {
            throw new InvalidTokenException('Invalid audience');
        }
    }

    /** @return array<string, \Firebase\JWT\Key> */
    private function getPublicKeys(): array
    {
        $jwksUrl = "{$this->keycloakUrl}/realms/{$this->keycloakRealm}/protocol/openid-connect/certs";

        $context = stream_context_create([
            'http' => [
                'timeout' => 5,
                'method' => 'GET',
            ],
        ]);

        $jwks = @file_get_contents($jwksUrl, false, $context);
        if (false === $jwks) {
            throw new InvalidTokenException('Failed to load JWKS from '.$jwksUrl);
        }

        $jwksData = json_decode($jwks, true);
        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new InvalidTokenException('Invalid JWKS JSON: '.json_last_error_msg());
        }

        return JWK::parseKeySet($jwksData);
    }
}
