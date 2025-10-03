<?php

namespace App\Security;

use Symfony\Component\Security\Core\User\UserInterface;

class JwtUser implements UserInterface
{
    /**
     * @param string[]             $roles
     * @param array<string, mixed> $claims
     */
    public function __construct(
        private readonly string $identifier,
        private readonly array $roles = ['ROLE_USER'],
        private readonly array $claims = [],
    ) {
    }

    /** @return string[] */
    public function getRoles(): array
    {
        return $this->roles;
    }

    public function eraseCredentials(): void
    {
        // Nothing to erase
    }

    public function getUserIdentifier(): string
    {
        return $this->identifier;
    }

    /** @return array<string, mixed> */
    public function getClaims(): array
    {
        return $this->claims;
    }

    public function getClaim(string $key): mixed
    {
        return $this->claims[$key] ?? null;
    }
}
