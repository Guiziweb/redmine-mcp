<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Domain\Model\UserCredential;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Doctrine-based implementation for production use.
 * Credentials are encrypted using Sodium before storage.
 */
final class DoctrineUserCredentialRepository implements UserCredentialRepository
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly EncryptionService $encryption,
    ) {
    }

    public function findByUserId(string $userId): UserCredential
    {
        $repository = $this->em->getRepository(UserCredential::class);
        $credential = $repository->find($userId);

        if (!$credential) {
            throw new \RuntimeException(sprintf('No credentials found for user "%s"', $userId));
        }

        // Decrypt sensitive data
        $decryptedUrl = $this->encryption->decrypt($credential->redmineUrl);
        $decryptedApiKey = $this->encryption->decrypt($credential->redmineApiKey);

        return new UserCredential(
            userId: $credential->userId,
            redmineUrl: $decryptedUrl,
            redmineApiKey: $decryptedApiKey,
            createdAt: $credential->createdAt,
            role: $credential->role,
            isBot: $credential->isBot,
        );
    }

    public function save(UserCredential $credential): void
    {
        $repository = $this->em->getRepository(UserCredential::class);
        $existing = $repository->find($credential->userId);

        // Encrypt sensitive data before storing
        $encryptedUrl = $this->encryption->encrypt($credential->redmineUrl);
        $encryptedApiKey = $this->encryption->encrypt($credential->redmineApiKey);

        if ($existing) {
            // Update existing
            $existing->redmineUrl = $encryptedUrl;
            $existing->redmineApiKey = $encryptedApiKey;
            $existing->role = $credential->role;
            $existing->isBot = $credential->isBot;
        } else {
            // Create new
            $encrypted = new UserCredential(
                userId: $credential->userId,
                redmineUrl: $encryptedUrl,
                redmineApiKey: $encryptedApiKey,
                createdAt: $credential->createdAt ?? new \DateTimeImmutable(),
                role: $credential->role,
                isBot: $credential->isBot,
            );
            $this->em->persist($encrypted);
        }

        $this->em->flush();
    }

    public function exists(string $userId): bool
    {
        $repository = $this->em->getRepository(UserCredential::class);

        return null !== $repository->find($userId);
    }
}
