<?php

namespace App\Services\Cache;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CacheManager
{
    protected CourseCache $courseCache;
    protected UserCache $userCache;
    protected DashboardCache $dashboardCache;

    public function __construct(
        CourseCache $courseCache,
        UserCache $userCache,
        DashboardCache $dashboardCache
    ) {
        $this->courseCache = $courseCache;
        $this->userCache = $userCache;
        $this->dashboardCache = $dashboardCache;
    }

    /**
     * Clear all cache for a tenant
     */
    public function clearTenantCache(int $tenantId): void
    {
        try {
            // Clear course-related cache
            $this->courseCache->clearTenantCoursesCache($tenantId);
            
            // Clear user-related cache
            $this->userCache->clearTenantUsersCache($tenantId);
            
            // Clear dashboard cache
            $this->dashboardCache->clearDashboardCache($tenantId);
            
            Log::info("Successfully cleared all cache for tenant {$tenantId}");
        } catch (\Exception $e) {
            Log::error("Failed to clear cache for tenant {$tenantId}: " . $e->getMessage());
        }
    }

    /**
     * Clear all cache when a course is updated
     */
    public function clearCourseRelatedCache(int $courseId, int $tenantId): void
    {
        try {
            // Clear course-specific cache
            $this->courseCache->clearCourseCache($courseId, $tenantId);
            
            // Clear dashboard cache as course stats may have changed
            $this->dashboardCache->clearDashboardCache($tenantId);
            
            Log::info("Successfully cleared course-related cache for course {$courseId}, tenant {$tenantId}");
        } catch (\Exception $e) {
            Log::error("Failed to clear course-related cache for course {$courseId}: " . $e->getMessage());
        }
    }

    /**
     * Clear all cache when a user is updated
     */
    public function clearUserRelatedCache(int $userId, int $tenantId): void
    {
        try {
            // Clear user-specific cache
            $this->userCache->clearUserCache($userId);
            
            // Clear tenant users cache
            $this->userCache->clearTenantUsersCache($tenantId);
            
            // Clear dashboard cache as user stats may have changed
            $this->dashboardCache->clearDashboardCache($tenantId);
            
            Log::info("Successfully cleared user-related cache for user {$userId}, tenant {$tenantId}");
        } catch (\Exception $e) {
            Log::error("Failed to clear user-related cache for user {$userId}: " . $e->getMessage());
        }
    }

    /**
     * Clear cache when student progress is updated
     */
    public function clearProgressRelatedCache(int $userId, int $courseId, int $tenantId): void
    {
        try {
            // Clear user course progress cache
            $this->userCache->clearUserCourseProgressCache($userId, $courseId);
            
            // Clear student progress cache
            $this->courseCache->clearStudentProgressCache($courseId, $userId);
            
            // Clear dashboard cache as progress affects many metrics
            $this->dashboardCache->clearDashboardCache($tenantId);
            
            Log::info("Successfully cleared progress-related cache for user {$userId}, course {$courseId}, tenant {$tenantId}");
        } catch (\Exception $e) {
            Log::error("Failed to clear progress-related cache for user {$userId}, course {$courseId}: " . $e->getMessage());
        }
    }

    /**
     * Clear cache when a purchase is made
     */
    public function clearPurchaseRelatedCache(int $userId, int $courseId, int $tenantId): void
    {
        try {
            // Clear user purchase cache
            $this->userCache->clearUserCache($userId);
            
            // Clear course cache as enrollment stats may have changed
            $this->courseCache->clearCourseCache($courseId, $tenantId);
            
            // Clear dashboard cache as revenue and purchase stats have changed
            $this->dashboardCache->clearDashboardCache($tenantId);
            
            Log::info("Successfully cleared purchase-related cache for user {$userId}, course {$courseId}, tenant {$tenantId}");
        } catch (\Exception $e) {
            Log::error("Failed to clear purchase-related cache for user {$userId}, course {$courseId}: " . $e->getMessage());
        }
    }

    /**
     * Clear cache when a certificate is issued
     */
    public function clearCertificateRelatedCache(int $userId, int $courseId, int $tenantId): void
    {
        try {
            // Clear user certificate cache
            $this->userCache->clearUserCache($userId);
            
            // Clear course cache as completion stats may have changed
            $this->courseCache->clearCourseCache($courseId, $tenantId);
            
            // Clear dashboard cache as certificate stats have changed
            $this->dashboardCache->clearDashboardCache($tenantId);
            
            Log::info("Successfully cleared certificate-related cache for user {$userId}, course {$courseId}, tenant {$tenantId}");
        } catch (\Exception $e) {
            Log::error("Failed to clear certificate-related cache for user {$userId}, course {$courseId}: " . $e->getMessage());
        }
    }

    /**
     * Warm up cache for a tenant
     */
    public function warmUpTenantCache(int $tenantId): void
    {
        try {
            // Warm up dashboard cache
            $this->dashboardCache->warmUpDashboardCache($tenantId);
            
            Log::info("Successfully warmed up cache for tenant {$tenantId}");
        } catch (\Exception $e) {
            Log::error("Failed to warm up cache for tenant {$tenantId}: " . $e->getMessage());
        }
    }

    /**
     * Get cache statistics
     */
    public function getCacheStats(): array
    {
        try {
            $redis = Cache::getRedis();
            $info = $redis->info();
            
            return [
                'redis_version' => $info['redis_version'] ?? 'Unknown',
                'used_memory' => $info['used_memory_human'] ?? 'Unknown',
                'connected_clients' => $info['connected_clients'] ?? 'Unknown',
                'keyspace_hits' => $info['keyspace_hits'] ?? 0,
                'keyspace_misses' => $info['keyspace_misses'] ?? 0,
                'hit_rate' => $this->calculateHitRate($info),
                'total_keys' => $this->getTotalKeys($redis),
            ];
        } catch (\Exception $e) {
            Log::error("Failed to get cache stats: " . $e->getMessage());
            return [
                'error' => 'Failed to retrieve cache statistics',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Calculate cache hit rate
     */
    protected function calculateHitRate(array $info): float
    {
        $hits = $info['keyspace_hits'] ?? 0;
        $misses = $info['keyspace_misses'] ?? 0;
        $total = $hits + $misses;
        
        return $total > 0 ? round(($hits / $total) * 100, 2) : 0;
    }

    /**
     * Get total number of keys in Redis
     */
    protected function getTotalKeys($redis): int
    {
        try {
            $keys = $redis->keys('*');
            return count($keys);
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Clear expired cache entries
     */
    public function clearExpiredCache(): void
    {
        try {
            $redis = Cache::getRedis();
            
            // Get all keys with TTL
            $keys = $redis->keys('*');
            $expiredCount = 0;
            
            foreach ($keys as $key) {
                $ttl = $redis->ttl($key);
                if ($ttl === -1) { // Key exists but has no TTL
                    // You might want to set a default TTL for keys without expiration
                    continue;
                }
                if ($ttl === -2) { // Key doesn't exist or has expired
                    $expiredCount++;
                }
            }
            
            Log::info("Cache cleanup completed. Found {$expiredCount} expired keys");
        } catch (\Exception $e) {
            Log::error("Failed to clear expired cache: " . $e->getMessage());
        }
    }

    /**
     * Flush all cache
     */
    public function flushAll(): void
    {
        try {
            Cache::flush();
            Log::info("Successfully flushed all cache");
        } catch (\Exception $e) {
            Log::error("Failed to flush cache: " . $e->getMessage());
        }
    }

    /**
     * Get cache keys by pattern
     */
    public function getCacheKeysByPattern(string $pattern): array
    {
        try {
            $redis = Cache::getRedis();
            return $redis->keys($pattern);
        } catch (\Exception $e) {
            Log::error("Failed to get cache keys by pattern: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get cache value by key
     */
    public function getCacheValue(string $key): mixed
    {
        try {
            return Cache::get($key);
        } catch (\Exception $e) {
            Log::error("Failed to get cache value for key {$key}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Set cache value
     */
    public function setCacheValue(string $key, mixed $value, int $ttl = 3600): bool
    {
        try {
            return Cache::put($key, $value, $ttl);
        } catch (\Exception $e) {
            Log::error("Failed to set cache value for key {$key}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete cache key
     */
    public function deleteCacheKey(string $key): bool
    {
        try {
            return Cache::forget($key);
        } catch (\Exception $e) {
            Log::error("Failed to delete cache key {$key}: " . $e->getMessage());
            return false;
        }
    }
}
