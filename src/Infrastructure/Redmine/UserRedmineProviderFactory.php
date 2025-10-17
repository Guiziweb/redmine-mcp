<?php

declare(strict_types=1);

namespace App\Infrastructure\Redmine;

use App\Api\RedmineService;
use App\Domain\Model\UserCredential;
use App\Domain\Provider\TimeTrackingProviderInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Factory for creating user-specific Redmine providers.
 * Respects DDD by encapsulating the creation logic.
 */
final readonly class UserRedmineProviderFactory
{
    public function __construct(
        private DenormalizerInterface $serializer,
        private \Psr\Log\LoggerInterface $logger,
    ) {
    }

    /**
     * Create a Redmine provider for a specific user.
     */
    public function createForUser(UserCredential $credential): TimeTrackingProviderInterface
    {
        // Create Redmine HTTP client with user's credentials
        $redmineService = new RedmineService(
            $credential->redmineUrl,
            $credential->redmineApiKey,
            $this->logger
        );

        // Create provider with Redmine API implementation
        return new RedmineProvider($redmineService, $this->serializer);
    }
}
