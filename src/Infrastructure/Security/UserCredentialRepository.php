<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Domain\Model\UserCredential;

/**
 * Repository for managing user credentials in multi-tenant mode.
 * In production, this would be backed by a database with encrypted storage.
 */
interface UserCredentialRepository
{
    /**
     * Find credentials for a specific user.
     *
     * @throws \RuntimeException if credentials not found
     */
    public function findByUserId(string $userId): UserCredential;

    /**
     * Store or update credentials for a user.
     */
    public function save(UserCredential $credential): void;

    /**
     * Check if credentials exist for a user.
     */
    public function exists(string $userId): bool;
}
