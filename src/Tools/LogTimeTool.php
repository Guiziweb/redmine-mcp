<?php

declare(strict_types=1);

namespace App\Tools;

use App\Domain\Provider\TimeTrackingProviderInterface;
use App\Domain\Service\TimeEntryService;
use Mcp\Capability\Attribute\McpTool;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

#[Autoconfigure(public: true)]
final class LogTimeTool
{
    public function __construct(
        private readonly TimeEntryService $timeEntryService,
        private readonly TimeTrackingProviderInterface $provider,
    ) {
    }

    /**
     * Log time spent on an issue.
     *
     * Can specify the date to log time on (defaults to today).
     * Some providers require activity_id (use list_activities tool first to get valid IDs).
     *
     * @param int         $issue_id    The issue ID to log time against
     * @param float       $hours       Number of hours to log
     * @param string      $comment     Comments for the time entry
     * @param int|null    $activity_id Activity ID (required for some providers like Redmine, use list_activities tool to get valid IDs)
     * @param string|null $spent_on    Date in YYYY-MM-DD format (e.g., "2025-10-07"). Defaults to today if not specified.
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'log_time')]
    public function logTime(
        int $issue_id,
        float $hours,
        string $comment,
        ?int $activity_id = null,
        ?string $spent_on = null,
    ): array {
        try {
            $capabilities = $this->provider->getCapabilities();

            // Validate activity requirement
            if ($capabilities->requiresActivity && null === $activity_id) {
                return [
                    'success' => false,
                    'error' => sprintf(
                        'Provider "%s" requires an activity_id. Please use list_activities tool to get valid IDs.',
                        $capabilities->name
                    ),
                ];
            }

            // Parse date
            $spentAt = $spent_on ? new \DateTime($spent_on) : new \DateTime('today');

            // Prepare metadata
            $metadata = [];
            if (null !== $activity_id) {
                $metadata['activity_id'] = $activity_id;
            }

            // Log time through service
            $timeEntry = $this->timeEntryService->logTime(
                issueId: $issue_id,
                hours: $hours,
                comment: $comment,
                spentAt: $spentAt,
                metadata: $metadata
            );

            // Format response
            return [
                'success' => true,
                'time_entry' => [
                    'id' => $timeEntry->id,
                    'issue_id' => $timeEntry->issue->id,
                    'hours' => $timeEntry->getHours(),
                    'comment' => $timeEntry->comment,
                    'spent_on' => $timeEntry->spentAt->format('Y-m-d'),
                    'activity' => $timeEntry->activity ? $timeEntry->activity->name : null,
                ],
            ];
        } catch (\Throwable $e) {
            // Return error with detailed message so AI can see it and self-correct
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
