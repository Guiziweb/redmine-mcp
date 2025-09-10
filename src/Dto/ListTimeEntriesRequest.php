<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class ListTimeEntriesRequest implements DtoInterface
{
    public function __construct(
        #[Assert\Date]
        public readonly ?string $from = null,
        #[Assert\Date]
        public readonly ?string $to = null,
        #[Assert\Positive]
        #[Assert\Range(min: 1, max: 100)]
        public readonly int $limit = 100,
        #[Assert\Positive]
        public readonly ?int $projectId = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            from: $data['from'] ?? null,
            to: $data['to'] ?? null,
            limit: $data['limit'] ?? 100,
            projectId: isset($data['project_id']) ? (int) $data['project_id'] : null,
        );
    }
}
