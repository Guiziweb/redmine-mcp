<?php

declare(strict_types=1);

namespace App\Client;

use App\Api\RedmineService;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * Client for project-related Redmine operations.
 */
#[Autoconfigure(public: true)]
class ProjectClient
{
    public function __construct(
        private readonly RedmineService $redmineService,
    ) {
    }

    /** @return array<string, mixed>[] */
    public function getMyProjects(): array
    {
        $data = $this->redmineService->getMyProjects();

        $projects = $data['projects'] ?? [];

        // Return only id, name and parent with defensive mapping
        return array_map(function ($project) {
            return [
                'id' => $project['id'] ?? 0,
                'name' => $project['name'] ?? 'Unknown',
                'parent' => isset($project['parent']) ? [
                    'id' => $project['parent']['id'] ?? 0,
                    'name' => $project['parent']['name'] ?? 'Unknown',
                ] : null,
            ];
        }, $projects);
    }
}
