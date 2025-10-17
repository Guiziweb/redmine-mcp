<?php

namespace App\Api;

use Psr\Log\LoggerInterface;
use Redmine\Client\NativeCurlClient;

class RedmineService
{
    public function __construct(
        private readonly string $redmineUrl,
        private readonly string $redmineApiKey,
        private readonly LoggerInterface $logger,
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
     *
     * @param int         $issueId Issue ID
     * @param float       $hours   Hours to log
     * @param string      $comment Comment/description
     *                             c     * @param int         $activityId Activity type ID
     * @param string|null $spentOn Date in YYYY-MM-DD format (defaults to today)
     *
     * @return array<string, mixed>
     */
    public function logTime(int $issueId, float $hours, string $comment, int $activityId, ?string $spentOn = null): array
    {
        $data = [
            'issue_id' => $issueId,
            'hours' => $hours,
            'comments' => $comment,
            'spent_on' => $spentOn ?? date('Y-m-d'), // Default to today
            'activity_id' => $activityId,
        ];

        $this->logger->info('LogTime called', ['data' => $data]);

        $client = $this->getClient();
        $api = $client->getApi('time_entry');

        try {
            $this->logger->info('Calling Redmine API create()');
            $result = $api->create($data);
            $this->logger->info('Redmine API returned', [
                'type' => gettype($result),
                'value' => $result instanceof \SimpleXMLElement ? $result->asXML() : $result,
            ]);

            // Check if result is an error response
            if ($result instanceof \SimpleXMLElement) {
                // Check for <errors> tag
                if (isset($result->error) || isset($result->errors)) {
                    $errors = [];
                    foreach ($result->error ?? $result->errors->error ?? [] as $error) {
                        $errors[] = (string) $error;
                    }
                    throw new \RuntimeException('Redmine API error: '.implode(', ', $errors));
                }

                return ['success' => true];
            }

            // Empty string means success (201 Created with no body)
            if ('' === $result) {
                return ['success' => true];
            }

            throw new \RuntimeException('Unexpected response from Redmine API: '.gettype($result));
        } catch (\Exception $e) {
            $this->logger->error('Exception during logTime', ['exception' => $e->getMessage()]);
            throw new \RuntimeException('Failed to create time entry in Redmine: '.$e->getMessage(), 0, $e);
        }
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
