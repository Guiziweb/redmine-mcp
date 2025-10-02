<?php

declare(strict_types=1);

namespace App\Tools;

use App\Client\CachedTimeEntryClient;
use Mcp\Capability\Attribute\McpTool;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * MCP tool to list available time entry activities.
 */
#[Autoconfigure(public: true)]
final class ListTimeActivitiesTool
{
    public function __construct(
        private readonly CachedTimeEntryClient $timeEntryClient,
    ) {
    }

    /**
     * List available time entry activities for logging time.
     *
     * @return array<string, mixed>[]
     */
    #[McpTool(
        name: 'redmine_list_activities',
        description: 'List available time entry activities for logging time'
    )]
    public function listActivities(): array
    {
        return $this->timeEntryClient->getTimeEntryActivities();
    }
}
