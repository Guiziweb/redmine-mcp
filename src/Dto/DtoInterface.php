<?php

declare(strict_types=1);

namespace App\Dto;

interface DtoInterface
{
    /**
     * Create DTO instance from array data.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self;
}
