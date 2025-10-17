<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * Validates JWT tokens and extracts user information.
 * Used by MCP server to authenticate requests from Claude Desktop.
 */
final class JwtTokenValidator
{
    public function __construct(
        private readonly string $jwtSecret,
    ) {
    }

    /**
     * Validate a JWT token and return the user ID.
     *
     * @throws \RuntimeException if token is invalid or expired
     */
    public function validateAndGetUserId(string $token): string
    {
        try {
            $decoded = JWT::decode($token, new Key($this->jwtSecret, 'HS256'));

            if (!isset($decoded->sub)) {
                throw new \RuntimeException('Token missing "sub" claim (user ID)');
            }

            return (string) $decoded->sub;
        } catch (\Exception $e) {
            throw new \RuntimeException('Invalid or expired token: '.$e->getMessage(), 0, $e);
        }
    }

    /**
     * Create a JWT token for a user (used by Authorization Server).
     *
     * @param array<string, mixed> $extraClaims
     */
    public function createToken(string $userId, int $expiresIn = 3600, array $extraClaims = []): string
    {
        $now = time();

        $payload = array_merge([
            'iss' => 'mcp-redmine-auth-server', // issuer
            'sub' => $userId, // subject (user ID)
            'iat' => $now, // issued at
            'exp' => $now + $expiresIn, // expiration
        ], $extraClaims);

        return JWT::encode($payload, $this->jwtSecret, 'HS256');
    }
}
