<?php

declare(strict_types=1);

namespace App\Infrastructure\Provider;

use App\Domain\Provider\TimeTrackingProviderInterface;
use App\Infrastructure\Redmine\UserRedmineProviderFactory;
use App\Infrastructure\Security\User;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Provides the TimeTrackingProvider for the current authenticated user.
 *
 * This service acts as a proxy that delegates to the user-specific provider
 * based on the current authenticated user from Symfony Security.
 *
 * This allows MCP Tools to be registered once with dependency injection,
 * while still serving different users with their own Redmine credentials.
 */
final readonly class CurrentUserProviderService implements TimeTrackingProviderInterface
{
    public function __construct(
        private Security $security,
        private UserRedmineProviderFactory $providerFactory,
    ) {
    }

    /**
     * Get the provider instance for the current user.
     */
    private function getCurrentProvider(): TimeTrackingProviderInterface
    {
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            throw new \RuntimeException('No authenticated user found. Ensure Symfony Security is configured properly.');
        }

        return $this->providerFactory->createForUser($user->getCredential());
    }

    /**
     * Check if current user is authorized to query another user's data.
     *
     * @throws \RuntimeException if user is not authorized
     */
    private function assertCanQueryUser(?string $userId): void
    {
        if (null === $userId) {
            return; // Querying own data is always allowed
        }

        $currentUser = $this->security->getUser();
        if (!$currentUser instanceof User) {
            throw new \RuntimeException('No authenticated user found');
        }

        // If querying own data, allow
        if ($userId === $currentUser->getUserIdentifier()) {
            return;
        }

        // Otherwise, require ROLE_ADMIN
        if (!$this->security->isGranted('ROLE_ADMIN')) {
            throw new \RuntimeException(
                'Access denied: Only administrators can query other users\' data'
            );
        }
    }

    public function getCapabilities(): \App\Domain\Provider\ProviderCapabilities
    {
        return $this->getCurrentProvider()->getCapabilities();
    }

    public function getCurrentUser(): \App\Domain\Model\User
    {
        return $this->getCurrentProvider()->getCurrentUser();
    }

    public function getProjects(): array
    {
        return $this->getCurrentProvider()->getProjects();
    }

    public function getIssues(?int $projectId = null, int $limit = 50, ?string $userId = null): array
    {
        $this->assertCanQueryUser($userId);

        return $this->getCurrentProvider()->getIssues($projectId, $limit, $userId);
    }

    public function getIssue(int $issueId): \App\Domain\Model\Issue
    {
        return $this->getCurrentProvider()->getIssue($issueId);
    }

    public function getActivities(): array
    {
        return $this->getCurrentProvider()->getActivities();
    }

    public function logTime(
        int $issueId,
        int $seconds,
        string $comment,
        \DateTimeInterface $spentAt,
        array $metadata = [],
    ): \App\Domain\Model\TimeEntry {
        return $this->getCurrentProvider()->logTime($issueId, $seconds, $comment, $spentAt, $metadata);
    }

    public function getTimeEntries(
        \DateTimeInterface $from,
        \DateTimeInterface $to,
        ?string $userId = null,
    ): array {
        $this->assertCanQueryUser($userId);

        return $this->getCurrentProvider()->getTimeEntries($from, $to, $userId);
    }
}
