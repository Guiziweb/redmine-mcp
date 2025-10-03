<?php

namespace App\Security;

use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/** @implements UserProviderInterface<JwtUser> */
class JwtUserProvider implements UserProviderInterface
{
    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof JwtUser) {
            throw new UnsupportedUserException(sprintf('Invalid user class "%s".', $user::class));
        }

        // JWT is stateless, no need to refresh
        return $user;
    }

    public function supportsClass(string $class): bool
    {
        return JwtUser::class === $class;
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        // This is never called because JwtAuthenticator creates the user
        // But required by interface
        return new JwtUser($identifier);
    }
}
