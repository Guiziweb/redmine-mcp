<?php

declare(strict_types=1);

namespace App\Tools;

use App\Client\IssueClient;
use Mcp\Capability\Attribute\McpTool;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * MCP tool to get detailed information about a specific Redmine issue.
 */
#[Autoconfigure(public: true)]
final class GetIssueDetailsTool
{
    public function __construct(
        private readonly IssueClient $issueClient,
    ) {
    }

    /**
     * Get detailed information about a specific Redmine issue.
     *
     * @param int $issue_id The ID of the issue to retrieve
     *
     * @return array<string, mixed>
     */
    #[McpTool(
        name: 'redmine_get_issue_details',
        description: 'Get detailed information about a specific Redmine issue by its ID. Returns comprehensive issue data including description, status, priority, assignee, dates, attachments, and more.'
    )]
    public function getIssueDetails(int $issue_id): array
    {
        return $this->issueClient->getIssueDetails($issue_id, ['attachments', 'relations', 'journals', 'watchers']);
    }
}
