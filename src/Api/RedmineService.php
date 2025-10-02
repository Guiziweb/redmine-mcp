<?php

namespace App\Api;

use Redmine\Client\NativeCurlClient;

class RedmineService
{
    public function __construct(
        private readonly string $redmineUrl,
        private readonly string $redmineApiKey,
    ) {
    }

    private function getClient(): NativeCurlClient
    {
        return new NativeCurlClient(
            $this->redmineUrl,
            $this->redmineApiKey
        );
    }

    /**
     * Récupère les tickets avec des paramètres de filtrage.
     */
    /**
     * @param array<string, mixed> $params
     *
     * @return array<string, mixed>
     */
    public function getIssues(array $params = []): array
    {
        $client = $this->getClient();
        $api = $client->getApi('issue');

        return $api->list($params);
    }

    /**
     * Get a specific issue by ID.
     *
     * @param array<string, mixed> $params
     *
     * @return array<string, mixed>
     */
    public function getIssue(int $issueId, array $params = []): array
    {
        $client = $this->getClient();
        $api = $client->getApi('issue');

        return $api->show($issueId, $params);
    }

    /**
     * Get current authenticated user account.
     */
    /**
     * @return array<string, mixed>
     */
    public function getMyAccount(): array
    {
        $client = $this->getClient();
        $api = $client->getApi('user');
        $result = $api->getCurrentUser();

        if (false === $result || !is_array($result) || !isset($result['user'])) {
            throw new \RuntimeException('Invalid response from getCurrentUser API');
        }

        return $result;
    }

    /**
     * Get projects where the current user is a member.
     */
    /**
     * @return array<string, mixed>
     */
    public function getMyProjects(): array
    {
        $client = $this->getClient();
        $api = $client->getApi('project');

        return $api->list(['membership' => true]);
    }

    /**
     * Get time entry activities.
     */
    /**
     * @return array<string, mixed>
     */
    public function getTimeEntryActivities(): array
    {
        $client = $this->getClient();
        $api = $client->getApi('time_entry_activity');

        return $api->list();
    }

    /**
     * Log time entry for an issue.
     */
    /**
     * @return array<string, mixed>
     */
    public function logTime(int $issueId, float $hours, string $comment, ?int $activityId = null): array
    {
        $data = [
            'issue_id' => $issueId,
            'hours' => $hours,
            'comments' => $comment,
        ];

        if (null !== $activityId) {
            $data['activity_id'] = $activityId;
        }

        $client = $this->getClient();
        $api = $client->getApi('time_entry');
        $api->create($data);

        return ['success' => true];
    }

    /**
     * Get time entries with optional filters.
     *
     * @param array<string, mixed> $params
     *
     * @return array<string, mixed>
     */
    public function getTimeEntries(array $params = []): array
    {
        $client = $this->getClient();
        $api = $client->getApi('time_entry');

        return $api->all($params);
    }
}
