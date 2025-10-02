<?php

declare(strict_types=1);

namespace App\Tools;

use App\Client\TimeEntryClient;
use Mcp\Capability\Attribute\McpTool;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * MCP tool to log time on Redmine issues.
 */
#[Autoconfigure(public: true)]
final class LogTimeTool
{
    public function __construct(
        private readonly TimeEntryClient $timeEntryClient,
    ) {
    }

    /**
     * Log time spent on a Redmine issue.
     *
     * @param int         $issue_id    The issue ID to log time against
     * @param float       $hours       Number of hours to log
     * @param string      $comment     Comments for the time entry
     * @param int|null    $activity_id Activity ID (use redmine_list_activities to get valid IDs)
     *
     * @return array<string, mixed>
     */
    #[McpTool(
        name: 'redmine_log_time',
        description: 'Log time spent on a Redmine issue. IMPORTANT: Always ask the user for each parameter individually: 1) How many hours? 2) What work was done (comment)? 3) What activity type? (use redmine_list_activities tool to show available activities first)'
    )]
    public function logTime(
        int $issue_id,
        float $hours,
        string $comment,
        ?int $activity_id = null
    ): array {
        return $this->timeEntryClient->logTime(
            $issue_id,
            $hours,
            $comment,
            $activity_id
        );
    }
}
