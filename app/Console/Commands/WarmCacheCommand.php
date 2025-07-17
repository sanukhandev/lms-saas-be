<?php

namespace App\Console\Commands;

use App\Services\Auth\AuthCacheService;
use App\Services\Course\CourseService;
use App\Services\Cache\CacheManager;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Course;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class WarmCacheCommand extends Command
{
    protected $signature = 'cache:warm
                          {--tenant= : Warm cache for specific tenant}
                          {--user= : Warm cache for specific user}
                          {--course= : Warm cache for specific course}
                          {--all : Warm cache for all entities}';

    protected $description = 'Warm Redis cache for better performance';

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
        $this->info('Starting cache warming...');

        try {
            if ($this->option('all')) {
                $this->warmAllCache();
            } elseif ($tenantId = $this->option('tenant')) {
                $this->warmTenantCache($tenantId);
            } elseif ($userId = $this->option('user')) {
                $this->warmUserCache($userId);
            } elseif ($courseId = $this->option('course')) {
                $this->warmCourseCache($courseId);
            } else {
                $this->warmEssentialCache();
            }

            $this->info('Cache warming completed successfully!');
            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Cache warming failed: ' . $e->getMessage());
            Log::error('Cache warming failed', ['error' => $e->getMessage()]);
            return self::FAILURE;
        }
    }

    /**
     * Warm all cache
     */
    private function warmAllCache(): void
    {
        $this->info('Warming all cache...');

        // Warm tenant cache
        $tenants = Tenant::where('status', 'active')->get();
        foreach ($tenants as $tenant) {
            $this->warmTenantCache($tenant->id);
        }

        // Warm user cache
        $users = User::where('is_active', true)->get();
        foreach ($users as $user) {
            $this->warmUserCache($user->id);
        }

        // Warm course cache
        $courses = Course::where('is_active', true)->get();
        foreach ($courses as $course) {
            $this->warmCourseCache($course->id);
        }
    }

    /**
     * Warm essential cache (most frequently accessed data)
     */
    private function warmEssentialCache(): void
    {
        $this->info('Warming essential cache...');

        // Warm active tenants
        $tenants = Tenant::where('status', 'active')->limit(10)->get();
        $this->line('Warming tenant cache...');
        foreach ($tenants as $tenant) {
            $this->authCache->getTenantBySlug($tenant->slug);
            $this->authCache->getTenantByDomain($tenant->domain);
        }

        // Warm active users
        $users = User::where('is_active', true)->limit(100)->get();
        $this->line('Warming user cache...');
        foreach ($users as $user) {
            $this->authCache->getUserById($user->id);
            $this->authCache->getUserPermissions($user->id);
        }

        // Warm popular courses
        $courses = Course::where('is_active', true)
            ->withCount('users')
            ->orderBy('users_count', 'desc')
            ->limit(50)
            ->get();
        $this->line('Warming course cache...');
        foreach ($courses as $course) {
            $this->courseService->getCourseById($course->id);
            $this->courseService->getCourseEnrollmentCount($course->id);
        }
    }

    /**
     * Warm cache for specific tenant
     */
    private function warmTenantCache(int $tenantId): void
    {
        $this->line("Warming cache for tenant {$tenantId}...");

        $tenant = Tenant::find($tenantId);
        if (!$tenant) {
            $this->error("Tenant {$tenantId} not found");
            return;
        }

        // Warm tenant basic data
        $this->authCache->getTenantBySlug($tenant->slug);
        $this->authCache->getTenantByDomain($tenant->domain);

        // Warm tenant users
        $users = User::where('tenant_id', $tenantId)->get();
        foreach ($users as $user) {
            $this->authCache->warmUserCache($user->id);
        }

        // Warm tenant courses
        $courses = Course::where('tenant_id', $tenantId)->get();
        foreach ($courses as $course) {
            $this->courseService->warmCourseCache($course->id);
        }
    }

    /**
     * Warm cache for specific user
     */
    private function warmUserCache(int $userId): void
    {
        $this->line("Warming cache for user {$userId}...");
        $this->authCache->warmUserCache($userId);
    }

    /**
     * Warm cache for specific course
     */
    private function warmCourseCache(int $courseId): void
    {
        $this->line("Warming cache for course {$courseId}...");
        $this->courseService->warmCourseCache($courseId);
    }
}
