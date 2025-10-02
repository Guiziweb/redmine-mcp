<?php

declare(strict_types=1);

namespace App\Client;

use App\Api\RedmineService;

use SymfonyComponentDependencyInjectionAttributeAutoconfigure;
/**
 * Client for issue-related Redmine operations.
 */
#[Autoconfigure(public: true)]
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

    /**
     * @param string[] $include
     *
     * @return array<string, mixed>
     */
    public function getIssueDetails(int $issueId, array $include = []): array
    {
        $params = [];

        // Add include parameters if specified
        if (!empty($include)) {
            $params['include'] = implode(',', $include);
        }

        $result = $this->redmineService->getIssue($issueId, $params);

        $issue = $result['issue'] ?? [];

        // Return comprehensive issue details with defensive mapping
        return [
            'id' => $issue['id'] ?? 0,
            'subject' => $issue['subject'] ?? 'Unknown',
            'description' => $issue['description'] ?? '',
            'project' => isset($issue['project']) ? [
                'id' => $issue['project']['id'] ?? 0,
                'name' => $issue['project']['name'] ?? 'Unknown',
            ] : null,
            'tracker' => isset($issue['tracker']) ? [
                'id' => $issue['tracker']['id'] ?? 0,
                'name' => $issue['tracker']['name'] ?? 'Unknown',
            ] : null,
            'status' => isset($issue['status']) ? [
                'id' => $issue['status']['id'] ?? 0,
                'name' => $issue['status']['name'] ?? 'Unknown',
            ] : ['id' => 0, 'name' => 'Unknown'],
            'priority' => isset($issue['priority']) ? [
                'id' => $issue['priority']['id'] ?? 0,
                'name' => $issue['priority']['name'] ?? 'Unknown',
            ] : null,
            'author' => isset($issue['author']) ? [
                'id' => $issue['author']['id'] ?? 0,
                'name' => $issue['author']['name'] ?? 'Unknown',
            ] : null,
            'assigned_to' => isset($issue['assigned_to']) ? [
                'id' => $issue['assigned_to']['id'] ?? 0,
                'name' => $issue['assigned_to']['name'] ?? 'Unknown',
            ] : null,
            'category' => isset($issue['category']) ? [
                'id' => $issue['category']['id'] ?? 0,
                'name' => $issue['category']['name'] ?? 'Unknown',
            ] : null,
            'fixed_version' => isset($issue['fixed_version']) ? [
                'id' => $issue['fixed_version']['id'] ?? 0,
                'name' => $issue['fixed_version']['name'] ?? 'Unknown',
            ] : null,
            'parent' => isset($issue['parent']) ? [
                'id' => $issue['parent']['id'] ?? 0,
            ] : null,
            'children' => $issue['children'] ?? [],
            'attachments' => $issue['attachments'] ?? [],
            'relations' => $issue['relations'] ?? [],
            'changesets' => $issue['changesets'] ?? [],
            'journals' => $issue['journals'] ?? [],
            'watchers' => $issue['watchers'] ?? [],
            'allowed_statuses' => $issue['allowed_statuses'] ?? [],
            'done_ratio' => $issue['done_ratio'] ?? 0,
            'estimated_hours' => $issue['estimated_hours'] ?? null,
            'spent_hours' => $issue['spent_hours'] ?? null,
            'start_date' => $issue['start_date'] ?? null,
            'due_date' => $issue['due_date'] ?? null,
            'created_on' => $issue['created_on'] ?? '',
            'updated_on' => $issue['updated_on'] ?? '',
            'closed_on' => $issue['closed_on'] ?? null,
            'custom_fields' => $issue['custom_fields'] ?? [],
        ];
    }
}
