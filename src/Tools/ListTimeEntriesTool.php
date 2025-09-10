<?php

declare(strict_types=1);

namespace App\Tools;

use App\Client\TimeEntryClient;
use App\Dto\ListTimeEntriesRequest;
use App\SchemaGenerator;
use Symfony\AI\McpSdk\Capability\Tool\MetadataInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class ListTimeEntriesTool extends AbstractMcpTool implements MetadataInterface
{
    public function __construct(
        private readonly TimeEntryClient $timeEntryClient,
        ValidatorInterface $validator,
        SchemaGenerator $schemaGenerator,
    ) {
        parent::__construct($validator, $schemaGenerator);
    }

    public function getName(): string
    {
        return 'redmine_get_my_time_entries';
    }

    public function getDescription(): string
    {
        return 'Get my time entries with optional date filtering and total calculation. Perfect for monthly time tracking and work hour analysis.';
    }

    public function getTitle(): string
    {
        return 'Get My Time Entries';
    }

    protected function getRequestClass(): string
    {
        return ListTimeEntriesRequest::class;
    }

    /** @return array<string, mixed> */
    protected function execute(object $request): array
    {
        assert($request instanceof ListTimeEntriesRequest);

        $timeEntries = $this->timeEntryClient->getMyTimeEntries(
            $request->limit,
            $request->from,
            $request->to,
            $request->projectId
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
                'from' => $request->from,
                'to' => $request->to,
                'project_filter' => $request->projectId,
            ],
        ];
    }

    protected function getErrorMessage(): string
    {
        return 'Failed to retrieve time entries';
    }
}
