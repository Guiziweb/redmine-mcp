<?php

declare(strict_types=1);

namespace App\Domain\Provider;

use App\Domain\Model\Activity;
use App\Domain\Model\Issue;
use App\Domain\Model\Project;
use App\Domain\Model\TimeEntry;
use App\Domain\Model\User;

/**
 * Interface for time tracking providers (Redmine, Jira, GitLab, etc.).
 */
interface TimeTrackingProviderInterface
{
    /**
     * Get provider capabilities.
     */
    public function getCapabilities(): ProviderCapabilities;

    /**
     * Get current authenticated user.
     */
    public function getCurrentUser(): User;

    /**
     * Get user's projects.
     *
     * @return Project[]
     */
    public function getProjects(): array;

    /**
     * Get user's issues, optionally filtered by project.
     *
     * @param int|null $projectId Project ID to filter by (optional)
     * @param int      $limit     Maximum number of issues to return
     *
     * @return Issue[]
     */
    public function getIssues(?int $projectId = null, int $limit = 50): array;

    /**
     * Get a specific issue by ID.
     */
    public function getIssue(int $issueId): Issue;

    /**
     * Get available activities (only for providers that support activities).
     *
     * @return Activity[]
     */
    public function getActivities(): array;

    /**
     * Log time on an issue.
     *
     * @param int                  $issueId  Issue identifier
     * @param int                  $seconds  Duration in seconds
     * @param string               $comment  Work description
     * @param \DateTimeInterface   $spentAt  When the work was done
     * @param array<string, mixed> $metadata Provider-specific metadata (e.g., activity_id for Redmine)
     */
    public function logTime(
        int $issueId,
        int $seconds,
        string $comment,
        \DateTimeInterface $spentAt,
        array $metadata = [],
    ): TimeEntry;

    /**
     * Get user's time entries within a date range.
     *
     * @return TimeEntry[]
     */
    public function getTimeEntries(
        \DateTimeInterface $from,
        \DateTimeInterface $to,
    ): array;
}
