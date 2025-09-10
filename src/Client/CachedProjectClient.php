<?php

declare(strict_types=1);

namespace App\Client;

use Psr\Cache\CacheItemPoolInterface;

/**
 * Cached wrapper for ProjectClient.
 */
class CachedProjectClient
{
    private const CACHE_TTL = 86400; // 1 day for projects

    public function __construct(
        private readonly ProjectClient $projectClient,
        private readonly CacheItemPoolInterface $cache,
    ) {
    }

    /** @return array<string, mixed>[] */
    public function getMyProjects(): array
    {
        $cacheKey = 'redmine_projects';
        $cacheItem = $this->cache->getItem($cacheKey);

        if ($cacheItem->isHit()) {
            return $cacheItem->get();
        }

        $projects = $this->projectClient->getMyProjects();

        $cacheItem->set($projects);
        $cacheItem->expiresAfter(self::CACHE_TTL);
        $this->cache->save($cacheItem);

        return $projects;
    }
}
