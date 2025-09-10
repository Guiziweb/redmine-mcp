<?php

namespace App\Api;

use Redmine\Client\NativeCurlClient;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class RedmineService
{
    private NativeCurlClient $client;

    public function __construct(
        #[Autowire('%redmine_url%')] string $redmineUrl,
        #[Autowire('%redmine_api_key%')] string $apiKey,
    ) {
        $this->client = new NativeCurlClient($redmineUrl, $apiKey);
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
        $api = $this->client->getApi('issue');

        return $api->list($params);
    }

    /**
     * Get current authenticated user account.
     */
    /**
     * @return array<string, mixed>
     */
    public function getMyAccount(): array
    {
        $api = $this->client->getApi('user');
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
        $api = $this->client->getApi('project');

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
        $api = $this->client->getApi('time_entry_activity');

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

        $api = $this->client->getApi('time_entry');
        $api->create($data);

        return ['success' => true];
    }

    /**
     * Get time entries with optional filters.
     *
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public function getTimeEntries(array $params = []): array
    {
        $api = $this->client->getApi('time_entry');
        
        return $api->all($params);
    }
}
