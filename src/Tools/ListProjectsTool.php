<?php

declare(strict_types=1);

namespace App\Tools;

use App\Client\CachedProjectClient;
use Mcp\Capability\Attribute\McpTool;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * MCP tool to list available Redmine projects.
 */
#[Autoconfigure(public: true)]
final class ListProjectsTool
{
    public function __construct(
        private readonly CachedProjectClient $projectClient,
    ) {
    }

    /**
     * List all Redmine projects the current user has access to.
     */
    #[McpTool(
        name: 'redmine_list_projects',
        description: 'List all Redmine projects the current user has access to. IMPORTANT: Show the COMPLETE list of ALL projects with their names and IDs to the user (do not summarize or show only examples). Then ASK THE USER which specific project they want to see issues for before calling redmine_list_issues with that project_id.'
    )]
    public function listProjects(): string
    {
        $projects = $this->projectClient->getMyProjects();

        return json_encode($projects, JSON_PRETTY_PRINT) ?: '[]';
    }
}
