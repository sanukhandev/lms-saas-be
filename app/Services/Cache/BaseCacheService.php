<?php

namespace App\Services\Cache;

use Illuminate\Support\Facades\Cache;

abstract class BaseCacheService
{
    protected int $shortTtl = 300; // 5 minutes - for frequently changing data
    protected int $defaultTtl = 3600; // 1 hour - for general data
    protected int $longTtl = 7200; // 2 hours - for rarely changing data
    protected int $veryLongTtl = 86400; // 24 hours - for very static data

    /**
     * Clear cache by pattern
     */
    protected function clearCacheByPattern(string $pattern): void
    {
        $keys = Cache::store('redis')->getRedis()->keys("*{$pattern}*");
        if (!empty($keys)) {
            Cache::store('redis')->getRedis()->del($keys);
        }
    }

    /**
     * Clear cache by key
     */
    protected function clearCacheByKey(string $key): void
    {
        Cache::forget($key);
    }

    /**
     * Clear cache by multiple keys
     */
    protected function clearCacheByKeys(array $keys): void
    {
        foreach ($keys as $key) {
            Cache::forget($key);
        }
    }

    /**
     * Get cache with tags for better invalidation
     */
    protected function rememberWithTags(string $key, array $tags, int $ttl, callable $callback)
    {
        return Cache::tags($tags)->remember($key, $ttl, $callback);
    }

    /**
     * Clear cache by tags
     */
    protected function clearCacheByTags(array $tags): void
    {
        Cache::tags($tags)->flush();
    }

    /**
     * Get cache key for tenant-specific data
     */
    protected function getTenantCacheKey(string $type, int $tenantId, string $suffix = ''): string
    {
        return "{$type}_tenant_{$tenantId}" . ($suffix ? "_{$suffix}" : '');
    }

    /**
     * Get cache key for user-specific data
     */
    protected function getUserCacheKey(string $type, int $userId, string $suffix = ''): string
    {
        return "{$type}_user_{$userId}" . ($suffix ? "_{$suffix}" : '');
    }

    /**
     * Get cache key for course-specific data
     */
    protected function getCourseCacheKey(string $type, int $courseId, string $suffix = ''): string
    {
        return "{$type}_course_{$courseId}" . ($suffix ? "_{$suffix}" : '');
    }

    /**
     * Warm cache with default data
     */
    protected function warmCache(string $key, callable $callback, int $ttl = null): void
    {
        Cache::remember($key, $ttl ?? $this->defaultTtl, $callback);
    }

    /**
     * Check if cache exists
     */
    protected function cacheExists(string $key): bool
    {
        return Cache::has($key);
    }

    /**
     * Get cache expiration time
     */
    protected function getCacheExpiration(string $key): ?int
    {
        return Cache::store('redis')->getRedis()->ttl($key);
    }
}
