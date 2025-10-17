<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Domain\Model\UserCredential;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Symfony Security User that wraps Redmine credentials.
 * This allows us to use Symfony's security system while storing per-user Redmine API keys.
 */
final readonly class User implements UserInterface
{
    public function __construct(
        private UserCredential $credential,
    ) {
    }

    /**
     * Get the underlying Redmine credential.
     */
    public function getCredential(): UserCredential
    {
        return $this->credential;
    }

    /**
     * @return non-empty-string
     */
    public function getUserIdentifier(): string
    {
        return $this->credential->userId;
    }

    /**
     * @return string[]
     */
    public function getRoles(): array
    {
        // All authenticated users have ROLE_USER
        return ['ROLE_USER'];
    }

    #[\Deprecated]
    public function eraseCredentials(): void
    {
        // Nothing to erase - we need the API key
    }
}
