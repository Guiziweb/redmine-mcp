<?php

declare(strict_types=1);

namespace App\Domain\Model;

use Doctrine\ORM\Mapping as ORM;

/**
 * Represents a user's Redmine credentials.
 * Each user in the multi-tenant system has their own Redmine instance.
 *
 * Note: Credentials (redmineUrl, redmineApiKey) are stored encrypted in database.
 * The repository handles encryption/decryption transparently.
 */
#[ORM\Entity]
#[ORM\Table(name: 'user_credentials')]
class UserCredential
{
    public function __construct(
        #[ORM\Id]
        #[ORM\Column(type: 'string', length: 255)]
        public string $userId,

        #[ORM\Column(type: 'text')]
        public string $redmineUrl,

        #[ORM\Column(type: 'text')]
        public string $redmineApiKey,

        #[ORM\Column(type: 'datetime_immutable')]
        public ?\DateTimeInterface $createdAt = null,

        #[ORM\Column(type: 'string', length: 50)]
        public string $role = 'user',

        #[ORM\Column(type: 'boolean')]
        public bool $isBot = false,
    ) {
        $this->createdAt ??= new \DateTimeImmutable();
    }

    public function isAdmin(): bool
    {
        return 'admin' === $this->role;
    }
}
