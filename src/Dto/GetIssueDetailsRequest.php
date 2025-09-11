<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * DTO for get issue details requests with validation constraints.
 */
final class GetIssueDetailsRequest implements DtoInterface
{
    #[Assert\NotNull(message: 'Issue ID is required')]
    #[Assert\Positive(message: 'Issue ID must be a positive integer')]
    public int $issueId;

    /**
     * @var string[]
     */
    #[Assert\Choice(
        choices: ['children', 'attachments', 'relations', 'changesets', 'journals', 'watchers', 'allowed_statuses'],
        multiple: true,
        message: 'Invalid include option. Valid options are: children, attachments, relations, changesets, journals, watchers, allowed_statuses'
    )]
    public array $include = [];

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $request = new self();
        $request->issueId = (int) ($data['issue_id'] ?? 0);
        $request->include = isset($data['include']) && is_array($data['include']) ? $data['include'] : [];

        return $request;
    }
}
