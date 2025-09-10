<?php

declare(strict_types=1);

namespace App\Tools;

use App\Client\IssueClient;
use App\Dto\ListIssuesRequest;
use App\SchemaGenerator;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * MCP tool to list user's Redmine issues.
 */
final class ListIssuesTool extends AbstractMcpTool
{
    public function __construct(
        private readonly IssueClient $issueClient,
        ValidatorInterface $validator,
        SchemaGenerator $schemaGenerator,
    ) {
        parent::__construct($validator, $schemaGenerator);
    }

    public function getName(): string
    {
        return 'redmine_list_issues';
    }

    public function getDescription(): string
    {
        return 'List Redmine issues from ONE specific project. IMPORTANT: You must ASK THE USER which project they want to see issues for (show them the list from redmine_list_projects first), then call this tool with that single project_id. Do NOT try to fetch issues from multiple projects automatically.';
    }

    public function getTitle(): string
    {
        return 'List Issues';
    }

    protected function getRequestClass(): string
    {
        return ListIssuesRequest::class;
    }

    protected function getErrorMessage(): string
    {
        return 'Failed to list issues';
    }

    /** @return array<string, mixed>[] */
    protected function execute(object $request): array
    {
        assert($request instanceof ListIssuesRequest);
        return $this->issueClient->getMyIssues($request->limit, $request->projectId);
    }
}
