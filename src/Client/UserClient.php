<?php

declare(strict_types=1);

namespace App\Client;

use App\Api\RedmineService;
use App\Exception\RedmineException;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * Client for user-related Redmine operations.
 */
#[Autoconfigure(public: true)]
class UserClient
{
    public function __construct(
        private readonly RedmineService $redmineService,
    ) {
    }

    /** @return array<string, mixed> */
    public function getCurrentUser(): array
    {
        $data = $this->redmineService->getMyAccount();

        if (!isset($data['user']) || empty($data['user'])) {
            throw RedmineException::authenticationFailed();
        }

        return $data['user'];
    }
}
