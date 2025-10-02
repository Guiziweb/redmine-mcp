<?php

declare(strict_types=1);

namespace App\Client;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * Cached wrapper for TimeEntryClient with selective caching.
 */
#[Autoconfigure(public: true)]
class CachedTimeEntryClient
{
    private const CACHE_TTL = 86400; // 1 day for activities

    public function __construct(
        private readonly TimeEntryClient $timeEntryClient,
        private readonly CacheItemPoolInterface $cache,
    ) {
    }

    /** @return array<string, mixed>[] */
    public function getTimeEntryActivities(): array
    {
        $cacheKey = 'redmine_time_activities';
        $cacheItem = $this->cache->getItem($cacheKey);

        if ($cacheItem->isHit()) {
            return $cacheItem->get();
        }

        $activities = $this->timeEntryClient->getTimeEntryActivities();

        $cacheItem->set($activities);
        $cacheItem->expiresAfter(self::CACHE_TTL);
        $this->cache->save($cacheItem);

        return $activities;
    }

    /** @return array<string, mixed> */
    public function logTime(int $issueId, float $hours, string $comment, ?int $activityId = null): array
    {
        // No caching for write operations
        return $this->timeEntryClient->logTime($issueId, $hours, $comment, $activityId);
    }

    /** @return array<string, mixed>[] */
    public function getMyTimeEntries(int $limit, ?string $from = null, ?string $to = null, ?int $projectId = null): array
    {
        // No caching for time entries (they change frequently)
        return $this->timeEntryClient->getMyTimeEntries($limit, $from, $to, $projectId);
    }
}
