<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * DTO for list issues requests with validation constraints.
 */
final class ListIssuesRequest implements DtoInterface
{
    #[Assert\NotNull(message: 'Project ID is required')]
    #[Assert\Positive(message: 'Project ID must be a positive integer')]
    public int $projectId;

    #[Assert\Range(
        min: 1,
        max: 100,
        notInRangeMessage: 'Limit must be between {{ min }} and {{ max }}'
    )]
    public int $limit = 25;

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $request = new self();
        $request->projectId = (int) ($data['project_id'] ?? 0);
        $request->limit = isset($data['limit']) ? (int) $data['limit'] : 25;

        return $request;
    }
}
