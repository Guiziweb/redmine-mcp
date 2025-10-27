<?php

declare(strict_types=1);

namespace App\Tools;

use App\Domain\Provider\TimeTrackingProviderInterface;
use Mcp\Capability\Attribute\McpTool;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

#[Autoconfigure(public: true)]
final class ListIssuesTool
{
    public function __construct(
        private readonly TimeTrackingProviderInterface $provider,
    ) {
    }

    /**
     * List issues assigned to a user from ONE specific project.
     *
     * Use list_projects tool first to get the list of available projects,
     * then ASK THE USER which project they want to see issues for,
     * and call this tool with that single project_id.
     *
     * @param int|null    $project_id Filter by project ID (use list_projects tool to get valid project IDs)
     * @param int         $limit      Maximum number of issues to return (default: 25)
     * @param string|null $user_id    User ID to query (admin-only, null = current user)
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'list_issues')]
    public function listIssues(?int $project_id = null, int $limit = 25, ?string $user_id = null): array
    {
        try {
            $issues = $this->provider->getIssues($project_id, $limit, $user_id);

            return [
                'success' => true,
                'issues' => array_map(
                    fn ($issue) => [
                        'id' => $issue->id,
                        'title' => $issue->title,
                        'description' => $issue->description,
                        'status' => $issue->status,
                        'project' => [
                            'id' => $issue->project->id,
                            'name' => $issue->project->name,
                        ],
                        'assignee' => $issue->assignee,
                        'tracker' => $issue->tracker,
                        'priority' => $issue->priority,
                    ],
                    $issues
                ),
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
