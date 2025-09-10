<?php

declare(strict_types=1);

namespace App\Tools;

use App\Client\CachedProjectClient;
use App\Dto\ListProjectsRequest;
use App\SchemaGenerator;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * MCP tool to list available Redmine projects.
 */
final class ListProjectsTool extends AbstractMcpTool
{
    public function __construct(
        private readonly CachedProjectClient $projectClient,
        ValidatorInterface $validator,
        SchemaGenerator $schemaGenerator,
    ) {
        parent::__construct($validator, $schemaGenerator);
    }

    public function getName(): string
    {
        return 'redmine_list_projects';
    }

    public function getDescription(): string
    {
        return 'List all Redmine projects the current user has access to. IMPORTANT: Show the COMPLETE list of ALL projects with their names and IDs to the user (do not summarize or show only examples). Then ASK THE USER which specific project they want to see issues for before calling redmine_list_issues with that project_id.';
    }

    public function getTitle(): string
    {
        return 'List Projects';
    }

    protected function getRequestClass(): string
    {
        return ListProjectsRequest::class;
    }

    protected function getErrorMessage(): string
    {
        return 'Failed to list projects';
    }

    /** @return array<string, mixed>[] */
    protected function execute(object $request): array
    {
        return $this->projectClient->getMyProjects();
    }
}
