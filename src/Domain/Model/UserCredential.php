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
final class UserCredential
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
    ) {
        $this->createdAt ??= new \DateTimeImmutable();
    }
}
