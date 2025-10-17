<?php

declare(strict_types=1);

namespace App\Infrastructure\Redmine\Normalizer;

use App\Domain\Model\Issue;
use App\Domain\Model\Project;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Denormalizes Redmine API issue data to Issue domain model.
 */
class IssueNormalizer implements DenormalizerInterface, DenormalizerAwareInterface
{
    use DenormalizerAwareTrait;

    /**
     * @param array<string, mixed> $context
     */
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): Issue
    {
        $issue = $data['issue'] ?? $data;

        // Denormalize nested project using ProjectNormalizer
        $project = $this->denormalizer->denormalize(
            $issue['project'] ?? [],
            Project::class,
            $format,
            $context
        );

        return new Issue(
            id: (int) ($issue['id'] ?? 0),
            title: (string) ($issue['subject'] ?? ''),
            description: (string) ($issue['description'] ?? ''),
            project: $project,
            status: (string) ($issue['status']['name'] ?? ''),
            assignee: isset($issue['assigned_to']['name']) ? (string) $issue['assigned_to']['name'] : null,
            tracker: isset($issue['tracker']['name']) ? (string) $issue['tracker']['name'] : null,
            priority: isset($issue['priority']['name']) ? (string) $issue['priority']['name'] : null,
        );
    }

    /**
     * @param array<string, mixed> $context
     */
    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return Issue::class === $type && 'redmine' === ($context['provider'] ?? null);
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            Issue::class => true,
        ];
    }
}
