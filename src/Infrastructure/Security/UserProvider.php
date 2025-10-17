<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * Loads Symfony Security Users from the credential repository.
 * Maps userId (from JWT) to User (with Redmine credentials).
 *
 * @implements UserProviderInterface<User>
 */
final readonly class UserProvider implements UserProviderInterface
{
    public function __construct(
        private UserCredentialRepository $credentialRepository,
    ) {
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        try {
            $credential = $this->credentialRepository->findByUserId($identifier);
        } catch (\RuntimeException $e) {
            throw new UserNotFoundException(sprintf('User "%s" not found or credentials missing.', $identifier), 0, $e);
        }

        return new User($credential);
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof User) {
            throw new \InvalidArgumentException(sprintf('Expected instance of %s, got %s', User::class, get_class($user)));
        }

        // Reload the user from the repository
        return $this->loadUserByIdentifier($user->getUserIdentifier());
    }

    public function supportsClass(string $class): bool
    {
        return User::class === $class;
    }
}
