<?php

declare(strict_types=1);

namespace App\Client;

use App\Api\RedmineService;

/**
 * Client for issue-related Redmine operations.
 */
class IssueClient
{
    public function __construct(
        private readonly RedmineService $redmineService,
        private readonly UserClient $userClient,
    ) {
    }

    /** @return array<string, mixed>[] */
    public function getMyIssues(int $limit = 25, ?int $projectId = null): array
    {
        // Get current user first
        $user = $this->userClient->getCurrentUser();

        // Build params for Redmine API - filter by user ID
        $params = [
            'assigned_to_id' => (int) $user['id'],
            'limit' => $limit,
        ];

        // Add project filter if specified
        if (null !== $projectId) {
            $params['project_id'] = $projectId;
        }

        $result = $this->redmineService->getIssues($params);

        $issues = $result['issues'] ?? [];

        // Return only essential fields to reduce payload with defensive mapping
        return array_map(function ($issue) {
            return [
                'id' => $issue['id'] ?? 0,
                'subject' => $issue['subject'] ?? 'Unknown',
                'status' => isset($issue['status']) ? [
                    'id' => $issue['status']['id'] ?? 0,
                    'name' => $issue['status']['name'] ?? 'Unknown',
                ] : ['id' => 0, 'name' => 'Unknown'],
                'assigned_to' => isset($issue['assigned_to']) ? [
                    'id' => $issue['assigned_to']['id'] ?? 0,
                    'name' => $issue['assigned_to']['name'] ?? 'Unknown',
                ] : null,
                'done_ratio' => $issue['done_ratio'] ?? 0,
                'estimated_hours' => $issue['estimated_hours'] ?? null,
                'created_on' => $issue['created_on'] ?? '',
                'updated_on' => $issue['updated_on'] ?? '',
            ];
        }, $issues);
    }
}
