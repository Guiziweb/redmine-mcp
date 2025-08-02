<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Redmine\Client\NativeCurlClient;

class RedmineService
{
    private NativeCurlClient $client;
    private LoggerInterface $logger;

    public function __construct(string $redmineUrl, string $apiKey, LoggerInterface $logger)
    {
        $this->client = new NativeCurlClient($redmineUrl, $apiKey);
        $this->logger = $logger;
    }

    public function callApi(string $endpoint, string $method = 'get', array $params = [])
    {
        $this->logger->debug("API Call: {$method} {$endpoint}", $params);

        try {
            $apiName = $this->extractApiNameFromEndpoint($endpoint);
            if (!$apiName) {
                throw new \Exception("Endpoint non supporté: {$endpoint}");
            }

            $api = $this->client->getApi($apiName);
            $result = $this->callApiMethod($api, $endpoint, $method, $params);

            $this->logger->debug('API Call successful', [
                'endpoint' => $endpoint,
                'count' => $this->getResultCount($result),
            ]);

            return $result;
        } catch (\Exception $e) {
            $this->logger->error("API Call failed: {$e->getMessage()}", ['endpoint' => $endpoint]);

            return $this->getEmptyResult();
        }
    }

    // Méthodes spécifiques pour compatibilité
    public function getIssues(array $params = [])
    {
        return $this->callApi('/issues.{format}', 'get', $params);
    }

    public function getIssueById(int $id)
    {
        return $this->callApi('/issues.{format}', 'get', ['id' => $id]);
    }

    public function getUsers(array $params = [])
    {
        return $this->callApi('/users.{format}', 'get', $params);
    }

    public function getProjects(array $params = [])
    {
        return $this->callApi('/projects.{format}', 'get', $params);
    }

    public function getTimeEntries(array $params = [])
    {
        return $this->callApi('/time_entries.{format}', 'get', $params);
    }

    public function getMyAccount()
    {
        return $this->callApi('/users/current.{format}', 'get');
    }

    private function extractApiNameFromEndpoint(string $endpoint): ?string
    {
        $mapping = [
            '/issues.{format}' => 'issue',
            '/projects.{format}' => 'project',
            '/users.{format}' => 'user',
            '/time_entries.{format}' => 'time_entry',
            '/attachments.{format}' => 'attachment',
            '/news.{format}' => 'news',
            '/wiki_pages.{format}' => 'wiki_page',
            '/journals.{format}' => 'journal',
            '/documents.{format}' => 'document',
            '/files.{format}' => 'file',
            '/issue_categories.{format}' => 'issue_category',
            '/issue_relations.{format}' => 'issue_relation',
            '/issue_statuses.{format}' => 'issue_status',
            '/trackers.{format}' => 'tracker',
            '/enumerations.{format}' => 'enumeration',
            '/roles.{format}' => 'role',
            '/groups.{format}' => 'group',
            '/memberships.{format}' => 'membership',
            '/versions.{format}' => 'version',
            '/custom_fields.{format}' => 'custom_field',
            '/queries.{format}' => 'query',
            '/watchers.{format}' => 'watcher',
        ];

        return $mapping[$endpoint] ?? null;
    }

    private function callApiMethod($api, string $endpoint, string $method, array $params)
    {
        if ('get' === $method) {
            // Si on a un paramètre 'id', utiliser show()
            if (isset($params['id'])) {
                $id = $params['id'];
                unset($params['id']); // Retirer l'ID des paramètres

                return $api->show($id, $params);
            }

            // Sinon, utiliser list()
            return $api->list($params);
        }

        throw new \Exception("Méthode non supportée: {$method}");
    }

    private function getResultCount($result): int
    {
        if (is_array($result)) {
            foreach (['issues', 'users', 'projects', 'time_entries', 'data'] as $key) {
                if (isset($result[$key]) && is_array($result[$key])) {
                    return count($result[$key]);
                }
            }
        }

        return 0;
    }

    private function getEmptyResult(): array
    {
        return [
            'data' => [],
            'total_count' => 0,
            'offset' => 0,
            'limit' => 25,
        ];
    }
}
