<?php

declare(strict_types=1);

namespace App\Tools;

use App\Client\IssueClient;
use Mcp\Capability\Attribute\McpTool;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * MCP tool to list user's Redmine issues.
 */
#[Autoconfigure(public: true)]
final class ListIssuesTool
{
    public function __construct(
        private readonly IssueClient $issueClient,
    ) {
    }

    /**
     * List Redmine issues assigned to current user.
     *
     * @param int|null $project_id Filter by project ID
     * @param int      $limit      Maximum number of issues to return (default: 25)
     *
     * @return array<string, mixed>[]
     */
    #[McpTool(
        name: 'redmine_list_issues',
        description: 'List Redmine issues from ONE specific project. IMPORTANT: You must ASK THE USER which project they want to see issues for (show them the list from redmine_list_projects first), then call this tool with that single project_id. Do NOT try to fetch issues from multiple projects automatically.'
    )]
    public function listIssues(?int $project_id = null, int $limit = 25): array
    {
        return $this->issueClient->getMyIssues($limit, $project_id);
    }
}
