<?php

namespace App\Console\Commands;

use App\Services\Auth\AuthCacheService;
use App\Services\Course\CourseService;
use App\Services\Cache\CacheManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ClearCacheCommand extends Command
{
    protected $signature = 'cache:clear-custom
                          {--tenant= : Clear cache for specific tenant}
                          {--user= : Clear cache for specific user}
                          {--course= : Clear cache for specific course}
                          {--type= : Clear cache by type (auth|course|dashboard|all)}
                          {--pattern= : Clear cache by pattern}';

    protected $description = 'Clear Redis cache selectively';

    protected AuthCacheService $authCache;
    protected CourseService $courseService;
    protected CacheManager $cacheManager;

    public function __construct(
        AuthCacheService $authCache,
        CourseService $courseService,
        CacheManager $cacheManager
    ) {
        parent::__construct();
        $this->authCache = $authCache;
        $this->courseService = $courseService;
        $this->cacheManager = $cacheManager;
    }

    public function handle(): int
    {
        $this->info('Starting cache clearing...');

        try {
            if ($pattern = $this->option('pattern')) {
                $this->clearCacheByPattern($pattern);
            } elseif ($type = $this->option('type')) {
                $this->clearCacheByType($type);
            } elseif ($tenantId = $this->option('tenant')) {
                $this->clearTenantCache($tenantId);
            } elseif ($userId = $this->option('user')) {
                $this->clearUserCache($userId);
            } elseif ($courseId = $this->option('course')) {
                $this->clearCourseCache($courseId);
            } else {
                $this->clearAllCache();
            }

            $this->info('Cache clearing completed successfully!');
            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Cache clearing failed: ' . $e->getMessage());
            Log::error('Cache clearing failed', ['error' => $e->getMessage()]);
            return self::FAILURE;
        }
    }

    /**
     * Clear cache by pattern
     */
    private function clearCacheByPattern(string $pattern): void
    {
        $this->line("Clearing cache by pattern: {$pattern}");

        // Using Redis directly for pattern-based clearing
        $redis = Cache::store('redis')->getRedis();
        $keys = $redis->keys("*{$pattern}*");

        if (!empty($keys)) {
            $redis->del($keys);
            $this->info("Cleared " . count($keys) . " cache entries");
        } else {
            $this->info("No cache entries found matching pattern: {$pattern}");
        }
    }

    /**
     * Clear cache by type
     */
    private function clearCacheByType(string $type): void
    {
        $this->line("Clearing cache by type: {$type}");

        switch ($type) {
            case 'auth':
                $this->authCache->clearAllAuthCache();
                break;
            case 'course':
                $this->clearCacheByPattern('course_');
                break;
            case 'dashboard':
                $this->clearCacheByPattern('dashboard_');
                $this->clearCacheByPattern('recent_activities_');
                $this->clearCacheByPattern('course_progress_');
                $this->clearCacheByPattern('user_progress_');
                $this->clearCacheByPattern('payment_stats_');
                break;
            case 'all':
                Cache::flush();
                break;
            default:
                $this->error("Unknown cache type: {$type}");
                return;
        }

        $this->info("Cleared {$type} cache");
    }

    /**
     * Clear cache for specific tenant
     */
    private function clearTenantCache(int $tenantId): void
    {
        $this->line("Clearing cache for tenant {$tenantId}...");

        $this->authCache->clearTenantCache($tenantId);
        $this->courseService->clearTenantCoursesCache($tenantId);
        $this->cacheManager->clearTenantCache($tenantId);
    }

    /**
     * Clear cache for specific user
     */
    private function clearUserCache(int $userId): void
    {
        $this->line("Clearing cache for user {$userId}...");
        $this->authCache->clearUserCache($userId);
    }

    /**
     * Clear cache for specific course
     */
    private function clearCourseCache(int $courseId): void
    {
        $this->line("Clearing cache for course {$courseId}...");
        $this->courseService->clearCourseCache($courseId);
    }

    /**
     * Clear all cache
     */
    private function clearAllCache(): void
    {
        $this->line('Clearing all cache...');
        Cache::flush();
    }
}
