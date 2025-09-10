<?php

declare(strict_types=1);

namespace App\Dto;

/**
 * DTO for list time activities requests
 * No parameters required for this request.
 */
final class ListTimeActivitiesRequest implements DtoInterface
{
    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self();
    }
}
