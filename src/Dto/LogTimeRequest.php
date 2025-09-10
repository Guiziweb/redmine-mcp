<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * DTO for log time requests with validation constraints.
 */
final class LogTimeRequest implements DtoInterface
{
    #[Assert\NotNull(message: 'Issue ID is required')]
    #[Assert\Positive(message: 'Issue ID must be a positive integer')]
    public int $issueId;

    #[Assert\NotNull(message: 'Hours is required')]
    #[Assert\Range(
        min: 0.1,
        max: 24,
        notInRangeMessage: 'Hours must be between {{ min }} and {{ max }}'
    )]
    public float $hours;

    #[Assert\NotBlank(message: 'Comment cannot be empty')]
    #[Assert\Length(
        max: 1000,
        maxMessage: 'Comment cannot be longer than {{ limit }} characters'
    )]
    public string $comment;

    #[Assert\NotNull(message: 'Activity ID is required')]
    #[Assert\Positive(message: 'Activity ID must be a positive integer')]
    public int $activityId;

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $request = new self();
        $request->issueId = (int) ($data['issue_id'] ?? 0);
        $request->hours = (float) ($data['hours'] ?? 0);
        $request->comment = (string) ($data['comment'] ?? '');
        $request->activityId = (int) ($data['activity_id'] ?? 0);

        return $request;
    }
}
