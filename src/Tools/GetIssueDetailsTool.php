<?php

declare(strict_types=1);

namespace App\Tools;

use App\Client\IssueClient;
use App\Dto\GetIssueDetailsRequest;
use App\SchemaGenerator;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * MCP tool to get detailed information about a specific Redmine issue.
 */
final class GetIssueDetailsTool extends AbstractMcpTool
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
        return 'redmine_get_issue_details';
    }

    public function getDescription(): string
    {
        return 'Get detailed information about a specific Redmine issue by its ID. Returns comprehensive issue data including description, status, priority, assignee, dates, attachments, and more.';
    }

    public function getTitle(): string
    {
        return 'Get Issue Details';
    }

    protected function getRequestClass(): string
    {
        return GetIssueDetailsRequest::class;
    }

    protected function getErrorMessage(): string
    {
        return 'Failed to get issue details';
    }

    /** @return array<string, mixed> */
    protected function execute(object $request): array
    {
        assert($request instanceof GetIssueDetailsRequest);

        return $this->issueClient->getIssueDetails($request->issueId, $request->include);
    }
}
