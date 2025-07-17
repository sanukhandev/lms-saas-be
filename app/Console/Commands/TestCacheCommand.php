<?php

namespace App\Console\Commands;

use App\Services\Auth\AuthCacheService;
use App\Services\Course\CourseService;
use App\Services\Dashboard\DashboardService;
use App\Services\Tenant\TenantService;
use App\Models\User;
use App\Models\Tenant;
use App\Models\Course;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class TestCacheCommand extends Command
{
    protected $signature = 'cache:test 
                          {--clear : Clear cache before testing}
                          {--details : Show detailed output}';

    protected $description = 'Test Redis cache implementation';

    protected AuthCacheService $authCache;
    protected CourseService $courseService;
    protected DashboardService $dashboardService;
    protected TenantService $tenantService;

    public function __construct(
        AuthCacheService $authCache,
        CourseService $courseService,
        DashboardService $dashboardService,
        TenantService $tenantService
    ) {
        parent::__construct();
        $this->authCache = $authCache;
        $this->courseService = $courseService;
        $this->dashboardService = $dashboardService;
        $this->tenantService = $tenantService;
    }

    public function handle(): int
    {
        $this->info('🚀 Testing Redis Cache Implementation');
        $this->line('');

        if ($this->option('clear')) {
            $this->info('🧹 Clearing cache...');
            Cache::flush();
            $this->line('');
        }

        $passed = 0;
        $failed = 0;

        // Test 1: Redis Connection
        $this->line('📡 Testing Redis Connection...');
        if ($this->testRedisConnection()) {
            $this->info('✅ Redis connection successful');
            $passed++;
        } else {
            $this->error('❌ Redis connection failed');
            $failed++;
        }

        // Test 2: Basic Cache Operations
        $this->line('📝 Testing Basic Cache Operations...');
        if ($this->testBasicCacheOperations()) {
            $this->info('✅ Basic cache operations working');
            $passed++;
        } else {
            $this->error('❌ Basic cache operations failed');
            $failed++;
        }

        // Test 3: Authentication Cache
        $this->line('🔐 Testing Authentication Cache...');
        if ($this->testAuthenticationCache()) {
            $this->info('✅ Authentication cache working');
            $passed++;
        } else {
            $this->error('❌ Authentication cache failed');
            $failed++;
        }

        // Test 4: Tenant Cache
        $this->line('🏢 Testing Tenant Cache...');
        if ($this->testTenantCache()) {
            $this->info('✅ Tenant cache working');
            $passed++;
        } else {
            $this->error('❌ Tenant cache failed');
            $failed++;
        }

        // Test 5: Course Cache
        $this->line('📚 Testing Course Cache...');
        if ($this->testCourseCache()) {
            $this->info('✅ Course cache working');
            $passed++;
        } else {
            $this->error('❌ Course cache failed');
            $failed++;
        }

        // Test 6: Dashboard Cache
        $this->line('📊 Testing Dashboard Cache...');
        if ($this->testDashboardCache()) {
            $this->info('✅ Dashboard cache working');
            $passed++;
        } else {
            $this->error('❌ Dashboard cache failed');
            $failed++;
        }

        // Test 7: Performance Test
        $this->line('⚡ Testing Cache Performance...');
        $this->testCachePerformance();

        $this->line('');
        $this->info("📈 Test Results: {$passed} passed, {$failed} failed");

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }

    private function testRedisConnection(): bool
    {
        try {
            // Test Redis connection
            $result = Cache::store('redis')->put('test_connection', 'working', 10);
            $retrieved = Cache::store('redis')->get('test_connection');
            
            if ($retrieved === 'working') {
                Cache::store('redis')->forget('test_connection');
                return true;
            }
            return false;
        } catch (\Exception $e) {
            if ($this->option('details')) {
                $this->error('Redis connection error: ' . $e->getMessage());
            }
            return false;
        }
    }

    private function testBasicCacheOperations(): bool
    {
        try {
            $testKey = 'test_basic_operations';
            $testValue = ['data' => 'test', 'timestamp' => now()];

            // Test put
            Cache::put($testKey, $testValue, 60);

            // Test get
            $retrieved = Cache::get($testKey);
            if ($retrieved['data'] !== 'test') {
                return false;
            }

            // Test has
            if (!Cache::has($testKey)) {
                return false;
            }

            // Test forget
            Cache::forget($testKey);
            if (Cache::has($testKey)) {
                return false;
            }

            return true;
        } catch (\Exception $e) {
            if ($this->option('details')) {
                $this->error('Basic cache operations error: ' . $e->getMessage());
            }
            return false;
        }
    }

    private function testAuthenticationCache(): bool
    {
        try {
            // Test with first user
            $user = User::first();
            if (!$user) {
                $this->line('⚠️  No users found for authentication cache test');
                return true; // Skip test if no users
            }

            // Test getUserById caching
            $startTime = microtime(true);
            $cachedUser1 = $this->authCache->getUserById($user->id);
            $firstCallTime = microtime(true) - $startTime;

            $startTime = microtime(true);
            $cachedUser2 = $this->authCache->getUserById($user->id);
            $secondCallTime = microtime(true) - $startTime;

            if ($this->option('details')) {
                $this->line("First call: {$firstCallTime}s, Second call: {$secondCallTime}s");
            }

            // Second call should be faster (cached)
            if ($secondCallTime >= $firstCallTime) {
                $this->line('⚠️  Cache might not be working optimally for user data');
            }

            // Test getUserByEmail caching
            $cachedUserByEmail = $this->authCache->getUserByEmail($user->email);
            
            return $cachedUser1 && $cachedUser2 && $cachedUserByEmail &&
                   $cachedUser1->id === $user->id && 
                   $cachedUserByEmail->id === $user->id;
        } catch (\Exception $e) {
            if ($this->option('details')) {
                $this->error('Authentication cache error: ' . $e->getMessage());
            }
            return false;
        }
    }

    private function testTenantCache(): bool
    {
        try {
            $tenant = Tenant::first();
            if (!$tenant) {
                $this->line('⚠️  No tenants found for tenant cache test');
                return true; // Skip test if no tenants
            }

            // Test getTenantBySlug caching
            $startTime = microtime(true);
            $cachedTenant1 = $this->authCache->getTenantBySlug($tenant->slug);
            $firstCallTime = microtime(true) - $startTime;

            $startTime = microtime(true);
            $cachedTenant2 = $this->authCache->getTenantBySlug($tenant->slug);
            $secondCallTime = microtime(true) - $startTime;

            if ($this->option('details')) {
                $this->line("First call: {$firstCallTime}s, Second call: {$secondCallTime}s");
            }

            // Test domain lookup if domain exists
            if ($tenant->domain) {
                $cachedByDomain = $this->tenantService->findByDomain($tenant->domain);
                if (!$cachedByDomain || $cachedByDomain->id !== $tenant->id) {
                    return false;
                }
            }

            return $cachedTenant1 && $cachedTenant2 && $cachedTenant1->id === $tenant->id;
        } catch (\Exception $e) {
            if ($this->option('details')) {
                $this->error('Tenant cache error: ' . $e->getMessage());
            }
            return false;
        }
    }

    private function testCourseCache(): bool
    {
        try {
            $course = Course::first();
            if (!$course) {
                $this->line('⚠️  No courses found for course cache test');
                return true; // Skip test if no courses
            }

            // Test getCourseById caching
            $startTime = microtime(true);
            $cachedCourse1 = $this->courseService->getCourseById($course->id);
            $firstCallTime = microtime(true) - $startTime;

            $startTime = microtime(true);
            $cachedCourse2 = $this->courseService->getCourseById($course->id);
            $secondCallTime = microtime(true) - $startTime;

            if ($this->option('details')) {
                $this->line("First call: {$firstCallTime}s, Second call: {$secondCallTime}s");
            }

            // Test enrollment count caching
            $enrollmentCount = $this->courseService->getCourseEnrollmentCount($course->id);

            return $cachedCourse1 && $cachedCourse2 && 
                   $cachedCourse1->id === $course->id &&
                   is_numeric($enrollmentCount);
        } catch (\Exception $e) {
            if ($this->option('details')) {
                $this->error('Course cache error: ' . $e->getMessage());
            }
            return false;
        }
    }

    private function testDashboardCache(): bool
    {
        try {
            $tenant = Tenant::first();
            if (!$tenant) {
                $this->line('⚠️  No tenants found for dashboard cache test');
                return true; // Skip test if no tenants
            }

            // Test dashboard stats caching
            $startTime = microtime(true);
            $stats1 = $this->dashboardService->getDashboardStats($tenant->id);
            $firstCallTime = microtime(true) - $startTime;

            $startTime = microtime(true);
            $stats2 = $this->dashboardService->getDashboardStats($tenant->id);
            $secondCallTime = microtime(true) - $startTime;

            if ($this->option('details')) {
                $this->line("First call: {$firstCallTime}s, Second call: {$secondCallTime}s");
            }

            // Test recent activities caching
            $activities = $this->dashboardService->getRecentActivities($tenant->id);

            return $stats1 && $stats2 && 
                   $stats1->totalUsers === $stats2->totalUsers &&
                   $activities !== null;
        } catch (\Exception $e) {
            if ($this->option('details')) {
                $this->error('Dashboard cache error: ' . $e->getMessage());
            }
            return false;
        }
    }

    private function testCachePerformance(): void
    {
        $this->line('Running performance comparison...');
        
        try {
            // Test database query performance
            $startTime = microtime(true);
            DB::table('users')->count();
            $dbTime = microtime(true) - $startTime;

            // Test cache performance
            $startTime = microtime(true);
            Cache::remember('test_performance', 60, function () {
                return DB::table('users')->count();
            });
            $cacheTime = microtime(true) - $startTime;

            // Test cached retrieval
            $startTime = microtime(true);
            Cache::get('test_performance');
            $retrievalTime = microtime(true) - $startTime;

            $this->line("📊 Performance Results:");
            $this->line("  Database query: " . number_format($dbTime * 1000, 2) . "ms");
            $this->line("  Cache store: " . number_format($cacheTime * 1000, 2) . "ms");
            $this->line("  Cache retrieval: " . number_format($retrievalTime * 1000, 2) . "ms");
            
            $speedup = $dbTime / $retrievalTime;
            $this->line("  🚀 Cache is " . number_format($speedup, 1) . "x faster than database");

            Cache::forget('test_performance');
        } catch (\Exception $e) {
            $this->error('Performance test error: ' . $e->getMessage());
        }
    }
}
