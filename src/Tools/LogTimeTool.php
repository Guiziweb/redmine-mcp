<?php

declare(strict_types=1);

namespace App\Tools;

use App\Client\TimeEntryClient;
use App\Dto\LogTimeRequest;
use App\SchemaGenerator;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * MCP tool to log time on Redmine issues.
 */
final class LogTimeTool extends AbstractMcpTool
{
    public function __construct(
        private readonly TimeEntryClient $timeEntryClient,
        ValidatorInterface $validator,
        SchemaGenerator $schemaGenerator,
    ) {
        parent::__construct($validator, $schemaGenerator);
    }

    public function getName(): string
    {
        return 'redmine_log_time';
    }

    public function getDescription(): string
    {
        return 'Log time spent on a Redmine issue. IMPORTANT: Always ask the user for each parameter individually: 1) How many hours? 2) What work was done (comment)? 3) What activity type? (use redmine_list_activities tool to show available activities first)';
    }

    public function getTitle(): string
    {
        return 'Log Time';
    }

    protected function getRequestClass(): string
    {
        return LogTimeRequest::class;
    }

    protected function getErrorMessage(): string
    {
        return 'Failed to log time entry';
    }

    /** @return array<string, mixed> */
    protected function execute(object $request): array
    {
        assert($request instanceof LogTimeRequest);
        return $this->timeEntryClient->logTime(
            $request->issueId,
            $request->hours,
            $request->comment,
            $request->activityId
        );
    }
}
