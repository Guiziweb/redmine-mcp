<?php

declare(strict_types=1);

namespace App\Client;

use App\Api\RedmineService;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * Client for time entry-related Redmine operations.
 */
#[Autoconfigure(public: true)]
class TimeEntryClient
{
    public function __construct(
        private readonly RedmineService $redmineService,
        private readonly UserClient $userClient,
    ) {
    }

    /** @return array<string, mixed>[] */
    public function getTimeEntryActivities(): array
    {
        $data = $this->redmineService->getTimeEntryActivities();

        $activities = $data['time_entry_activities'] ?? [];

        // Return only essential fields to reduce payload with defensive mapping
        return array_map(function ($activity) {
            return [
                'id' => $activity['id'] ?? 0,
                'name' => $activity['name'] ?? 'Unknown',
                'active' => $activity['active'] ?? true,
            ];
        }, $activities);
    }

    /** @return array<string, mixed> */
    public function logTime(int $issueId, float $hours, string $comment, ?int $activityId = null): array
    {
        return $this->redmineService->logTime($issueId, $hours, $comment, $activityId);
    }

    /** @return array<string, mixed>[] */
    public function getMyTimeEntries(int $limit, ?string $from = null, ?string $to = null, ?int $projectId = null): array
    {
        // Get current user first
        $user = $this->userClient->getCurrentUser();

        // Build params for Redmine API - filter by user ID
        $params = [
            'user_id' => (int) $user['id'],
            'limit' => $limit,
        ];

        // Add date filters if specified
        if (null !== $from) {
            $params['from'] = $from;
        }
        if (null !== $to) {
            $params['to'] = $to;
        }

        // Add project filter if specified
        if (null !== $projectId) {
            $params['project_id'] = $projectId;
        }

        $result = $this->redmineService->getTimeEntries($params);

        $timeEntries = $result['time_entries'] ?? [];

        // Return only essential fields with defensive mapping
        return array_map(function ($timeEntry) {
            return [
                'id' => $timeEntry['id'] ?? 0,
                'hours' => (float) ($timeEntry['hours'] ?? 0),
                'spent_on' => $timeEntry['spent_on'] ?? '',
                'comments' => $timeEntry['comments'] ?? '',
                'project' => isset($timeEntry['project']) ? [
                    'id' => $timeEntry['project']['id'] ?? 0,
                    'name' => $timeEntry['project']['name'] ?? 'Unknown',
                ] : null,
                'issue' => isset($timeEntry['issue']) ? [
                    'id' => $timeEntry['issue']['id'] ?? 0,
                    'subject' => $timeEntry['issue']['subject'] ?? 'Unknown',
                ] : null,
                'activity' => isset($timeEntry['activity']) ? [
                    'id' => $timeEntry['activity']['id'] ?? 0,
                    'name' => $timeEntry['activity']['name'] ?? 'Unknown',
                ] : null,
                'user' => isset($timeEntry['user']) ? [
                    'id' => $timeEntry['user']['id'] ?? 0,
                    'name' => $timeEntry['user']['name'] ?? 'Unknown',
                ] : null,
                'created_on' => $timeEntry['created_on'] ?? '',
                'updated_on' => $timeEntry['updated_on'] ?? '',
            ];
        }, $timeEntries);
    }
}
