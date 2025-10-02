<?php

declare(strict_types=1);

namespace App\Tools;

use App\Client\TimeEntryClient;
use Mcp\Capability\Attribute\McpTool;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

#[Autoconfigure(public: true)]
final class ListTimeEntriesTool
{
    public function __construct(
        private readonly TimeEntryClient $timeEntryClient,
    ) {
    }

    /**
     * Get my time entries with optional date filtering and total calculation.
     *
     * @param int         $limit      Maximum number of entries to return (default: 100)
     * @param string|null $from       Start date (YYYY-MM-DD)
     * @param string|null $to         End date (YYYY-MM-DD)
     * @param int|null    $project_id Filter by project ID
     *
     * @return array<string, mixed>
     */
    #[McpTool(
        name: 'redmine_list_time_entries',
        description: 'Get my time entries with optional date filtering and total calculation. Perfect for monthly time tracking and work hour analysis.'
    )]
    public function listTimeEntries(
        int $limit = 100,
        ?string $from = null,
        ?string $to = null,
        ?int $project_id = null
    ): array {
        $timeEntries = $this->timeEntryClient->getMyTimeEntries(
            $limit,
            $from,
            $to,
            $project_id
        );

        // Calculate totals
        $totalHours = 0.0;
        $projectTotals = [];
        $weeklyTotals = [];
        $dailyTotals = [];

        foreach ($timeEntries as $entry) {
            $hours = $entry['hours'];
            $totalHours += $hours;

            // Group by project
            if ($entry['project']) {
                $projectName = $entry['project']['name'];
                $projectTotals[$projectName] = ($projectTotals[$projectName] ?? 0) + $hours;
            }

            // Group by week (ISO 8601: YYYY-W##)
            $date = $entry['spent_on'];
            if ($date) {
                $weekKey = date('Y-\WW', strtotime($date));
                $weeklyTotals[$weekKey] = ($weeklyTotals[$weekKey] ?? 0) + $hours;
            }

            // Group by day
            $dailyTotals[$date] = ($dailyTotals[$date] ?? 0) + $hours;
        }

        // Calculate working days statistics
        $workingDays = count($dailyTotals);
        $averageHoursPerDay = $workingDays > 0 ? $totalHours / $workingDays : 0;

        return [
            'time_entries' => $timeEntries,
            'summary' => [
                'total_hours' => round($totalHours, 2),
                'total_entries' => count($timeEntries),
                'working_days' => $workingDays,
                'average_hours_per_day' => round($averageHoursPerDay, 2),
                'project_breakdown' => array_map(fn ($h) => round($h, 2), $projectTotals),
                'weekly_breakdown' => array_map(fn ($h) => round($h, 2), $weeklyTotals),
                'daily_breakdown' => array_map(fn ($h) => round($h, 2), $dailyTotals),
            ],
            'period' => [
                'from' => $from,
                'to' => $to,
                'project_filter' => $project_id,
            ],
        ];
    }
}
