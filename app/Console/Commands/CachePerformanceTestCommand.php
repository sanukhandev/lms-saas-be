<?php

namespace App\Console\Commands;

use App\Services\Auth\AuthCacheService;
use App\Services\Course\CourseService;
use App\Services\Dashboard\DashboardService;
use App\Models\User;
use App\Models\Tenant;
use App\Models\Course;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CachePerformanceTestCommand extends Command
{
    protected $signature = 'cache:performance
                          {--iterations=10 : Number of iterations to run}
                          {--clear : Clear cache before testing}';

    protected $description = 'Test cache performance with realistic scenarios';

    protected AuthCacheService $authCache;
    protected CourseService $courseService;
    protected DashboardService $dashboardService;

    public function __construct(
        AuthCacheService $authCache,
        CourseService $courseService,
        DashboardService $dashboardService
    ) {
        parent::__construct();
        $this->authCache = $authCache;
        $this->courseService = $courseService;
        $this->dashboardService = $dashboardService;
    }

    public function handle(): int
    {
        $iterations = (int) $this->option('iterations');

        $this->info('🚀 Redis Cache Performance Test');
        $this->line('');

        if ($this->option('clear')) {
            $this->info('🧹 Clearing cache...');
            Cache::flush();
            $this->line('');
        }

        // Test 1: Authentication Performance
        $this->testAuthenticationPerformance($iterations);

        // Test 2: Dashboard Performance
        $this->testDashboardPerformance($iterations);

        // Test 3: Course Data Performance
        $this->testCoursePerformance($iterations);

        // Test 4: Complex Query Performance
        $this->testComplexQueryPerformance($iterations);

        return self::SUCCESS;
    }

    private function testAuthenticationPerformance(int $iterations): void
    {
        $this->info('🔐 Testing Authentication Performance');

        $user = User::first();
        if (!$user) {
            $this->line('⚠️  No users found, skipping authentication test');
            return;
        }

        // Test without cache
        $this->line('Testing without cache...');
        $startTime = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            User::with(['tenant'])->find($user->id);
        }
        $dbTime = microtime(true) - $startTime;

        // Test with cache
        $this->line('Testing with cache...');
        $startTime = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            $this->authCache->getUserById($user->id);
        }
        $cacheTime = microtime(true) - $startTime;

        $this->displayResults('Authentication', $dbTime, $cacheTime, $iterations);
    }

    private function testDashboardPerformance(int $iterations): void
    {
        $this->info('📊 Testing Dashboard Performance');

        $tenant = Tenant::first();
        if (!$tenant) {
            $this->line('⚠️  No tenants found, skipping dashboard test');
            return;
        }

        // Test without cache
        $this->line('Testing without cache...');
        Cache::flush(); // Clear cache for accurate comparison
        $startTime = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            // Simulate dashboard data retrieval
            $totalUsers = User::where('tenant_id', $tenant->id)->count();
            $totalCourses = Course::where('tenant_id', $tenant->id)->count();
        }
        $dbTime = microtime(true) - $startTime;

        // Test with cache
        $this->line('Testing with cache...');
        $startTime = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            $this->dashboardService->getDashboardStats($tenant->id);
        }
        $cacheTime = microtime(true) - $startTime;

        $this->displayResults('Dashboard', $dbTime, $cacheTime, $iterations);
    }

    private function testCoursePerformance(int $iterations): void
    {
        $this->info('📚 Testing Course Performance');

        $course = Course::first();
        if (!$course) {
            $this->line('⚠️  No courses found, skipping course test');
            return;
        }

        // Test without cache
        $this->line('Testing without cache...');
        $startTime = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            Course::with(['category', 'contents', 'users'])->find($course->id);
        }
        $dbTime = microtime(true) - $startTime;

        // Test with cache
        $this->line('Testing with cache...');
        $startTime = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            $this->courseService->getCourseById($course->id);
        }
        $cacheTime = microtime(true) - $startTime;

        $this->displayResults('Course', $dbTime, $cacheTime, $iterations);
    }

    private function testComplexQueryPerformance(int $iterations): void
    {
        $this->info('🔄 Testing Complex Query Performance');

        $tenant = Tenant::first();
        if (!$tenant) {
            $this->line('⚠️  No tenants found, skipping complex query test');
            return;
        }

        // Test without cache
        $this->line('Testing without cache...');
        $startTime = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            $this->executeComplexQuery($tenant->id);
        }
        $dbTime = microtime(true) - $startTime;

        // Test with cache
        $this->line('Testing with cache...');
        $startTime = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            $cacheKey = "complex_query_{$tenant->id}";
            Cache::remember($cacheKey, 600, function () use ($tenant) {
                return $this->executeComplexQuery($tenant->id);
            });
        }
        $cacheTime = microtime(true) - $startTime;

        $this->displayResults('Complex Query', $dbTime, $cacheTime, $iterations);
    }

    private function executeComplexQuery(int $tenantId): array
    {
        // Simulate a complex query that would benefit from caching
        $userCount = User::where('tenant_id', $tenantId)->count();
        $courseCount = Course::where('tenant_id', $tenantId)->count();
        $enrollmentCount = DB::table('course_user')
            ->join('users', 'course_user.user_id', '=', 'users.id')
            ->where('users.tenant_id', $tenantId)
            ->count();

        return [
            'user_count' => $userCount,
            'course_count' => $courseCount,
            'enrollment_count' => $enrollmentCount,
            'generated_at' => now()
        ];
    }

    private function displayResults(string $test, float $dbTime, float $cacheTime, int $iterations): void
    {
        $dbAvg = ($dbTime / $iterations) * 1000;
        $cacheAvg = ($cacheTime / $iterations) * 1000;
        $speedup = $dbTime / $cacheTime;

        $this->line('');
        $this->line("📊 {$test} Results ({$iterations} iterations):");
        $this->line("  Database: " . number_format($dbTime * 1000, 2) . "ms total, " . number_format($dbAvg, 2) . "ms avg");
        $this->line("  Cache: " . number_format($cacheTime * 1000, 2) . "ms total, " . number_format($cacheAvg, 2) . "ms avg");

        if ($speedup > 1) {
            $this->line("  🚀 Cache is " . number_format($speedup, 1) . "x faster");
        } else {
            $this->line("  ⚠️  Cache is " . number_format(1 / $speedup, 1) . "x slower (cache warming needed)");
        }
        $this->line('');
    }
}
