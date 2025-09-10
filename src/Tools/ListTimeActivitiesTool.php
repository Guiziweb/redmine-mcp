<?php

declare(strict_types=1);

namespace App\Tools;

use App\Client\CachedTimeEntryClient;
use App\Dto\ListTimeActivitiesRequest;
use App\SchemaGenerator;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * MCP tool to list available time entry activities.
 */
final class ListTimeActivitiesTool extends AbstractMcpTool
{
    public function __construct(
        private readonly CachedTimeEntryClient $timeEntryClient,
        ValidatorInterface $validator,
        SchemaGenerator $schemaGenerator,
    ) {
        parent::__construct($validator, $schemaGenerator);
    }

    public function getName(): string
    {
        return 'redmine_list_activities';
    }

    public function getDescription(): string
    {
        return 'List available time entry activities for logging time';
    }

    public function getTitle(): string
    {
        return 'List Time Activities';
    }

    protected function getRequestClass(): string
    {
        return ListTimeActivitiesRequest::class;
    }

    protected function getErrorMessage(): string
    {
        return 'Failed to fetch time entry activities';
    }

    /** @return array<string, mixed>[] */
    protected function execute(object $request): array
    {
        return $this->timeEntryClient->getTimeEntryActivities();
    }
}
