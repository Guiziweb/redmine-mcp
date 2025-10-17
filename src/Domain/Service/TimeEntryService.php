<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Model\TimeEntry;
use App\Domain\Provider\TimeTrackingProviderInterface;

/**
 * Domain service for time entry management with business rules.
 */
class TimeEntryService
{
    public function __construct(
        private readonly TimeTrackingProviderInterface $provider,
    ) {
    }

    /**
     * Log time on an issue with business validation.
     *
     * @param array<string, mixed> $metadata
     *
     * @throws \InvalidArgumentException If validation fails
     */
    public function logTime(
        int $issueId,
        float $hours,
        string $comment,
        \DateTimeInterface $spentAt,
        array $metadata = [],
    ): TimeEntry {
        // Validate hours
        if ($hours <= 0) {
            throw new \InvalidArgumentException('Hours must be greater than 0');
        }

        // Get provider capabilities for validation
        $capabilities = $this->provider->getCapabilities();

        // Check daily limit
        $dailyTotal = $this->getDailyTotal($spentAt);
        if ($dailyTotal + $hours > $capabilities->maxDailyHours) {
            $remaining = $capabilities->maxDailyHours - $dailyTotal;
            throw new \InvalidArgumentException(sprintf('Daily limit exceeded. You already logged %.2f hours today. Maximum: %d hours. Remaining: %.2f hours.', $dailyTotal, $capabilities->maxDailyHours, max(0, $remaining)));
        }

        // Validate activity requirement
        if ($capabilities->requiresActivity && !isset($metadata['activity_id'])) {
            throw new \InvalidArgumentException(sprintf('Provider "%s" requires an activity_id in metadata', $capabilities->name));
        }

        // Convert hours to seconds
        $seconds = (int) ($hours * 3600);

        return $this->provider->logTime(
            issueId: $issueId,
            seconds: $seconds,
            comment: $comment,
            spentAt: $spentAt,
            metadata: $metadata
        );
    }

    /**
     * Get total hours logged for a specific date.
     */
    public function getDailyTotal(\DateTimeInterface $date): float
    {
        $startOfDay = \DateTime::createFromInterface($date)->setTime(0, 0, 0);
        $endOfDay = \DateTime::createFromInterface($date)->setTime(23, 59, 59);

        $entries = $this->provider->getTimeEntries($startOfDay, $endOfDay);

        $totalSeconds = array_reduce(
            $entries,
            fn (int $sum, TimeEntry $entry) => $sum + $entry->seconds,
            0
        );

        return $totalSeconds / 3600;
    }

    /**
     * Get time entries within a date range.
     *
     * @return TimeEntry[]
     */
    public function getTimeEntries(
        \DateTimeInterface $from,
        \DateTimeInterface $to,
    ): array {
        return $this->provider->getTimeEntries($from, $to);
    }

    /**
     * Get aggregated time entries by day.
     *
     * @return array<string, array{date: string, hours: float, entries: TimeEntry[]}>
     */
    public function getEntriesByDay(
        \DateTimeInterface $from,
        \DateTimeInterface $to,
    ): array {
        $entries = $this->provider->getTimeEntries($from, $to);

        $byDay = [];
        foreach ($entries as $entry) {
            $dateKey = $entry->spentAt->format('Y-m-d');

            if (!isset($byDay[$dateKey])) {
                $byDay[$dateKey] = [
                    'date' => $dateKey,
                    'hours' => 0.0,
                    'entries' => [],
                ];
            }

            $byDay[$dateKey]['hours'] += $entry->getHours();
            $byDay[$dateKey]['entries'][] = $entry;
        }

        ksort($byDay);

        return $byDay;
    }

    /**
     * Get aggregated time entries by project.
     *
     * @return array<int, array{project_id: int, project_name: string, hours: float, entries: TimeEntry[]}>
     */
    public function getEntriesByProject(
        \DateTimeInterface $from,
        \DateTimeInterface $to,
    ): array {
        $entries = $this->provider->getTimeEntries($from, $to);

        $byProject = [];
        foreach ($entries as $entry) {
            $projectId = $entry->issue->project->id;

            if (!isset($byProject[$projectId])) {
                $byProject[$projectId] = [
                    'project_id' => $projectId,
                    'project_name' => $entry->issue->project->name,
                    'hours' => 0.0,
                    'entries' => [],
                ];
            }

            $byProject[$projectId]['hours'] += $entry->getHours();
            $byProject[$projectId]['entries'][] = $entry;
        }

        return $byProject;
    }
}
