<?php

namespace App\Services\Dashboard;

use App\DTOs\Dashboard\{
    DashboardStatsDTO,
    ActivityDTO,
    UserDTO,
    CourseProgressDTO,
    UserProgressDTO,
    PaymentStatsDTO,
    ChartDataDTO
};
use App\Models\{Course, User, StudentProgress, CoursePurchase};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Carbon\Carbon;

class DashboardService
{
    /**
     * Get dashboard statistics for a tenant
     */
    public function getDashboardStats(string $tenantId): DashboardStatsDTO
    {
        return Cache::remember("dashboard_stats_{$tenantId}", 300, function () use ($tenantId) {
            $totalUsers = User::where('tenant_id', $tenantId)->count();
            $totalCourses = Course::where('tenant_id', $tenantId)->count();
            $totalEnrollments = $this->getTotalEnrollments($tenantId);
            $totalRevenue = $this->getTotalRevenue($tenantId);

            $userGrowthRate = $this->calculateUserGrowthRate($tenantId, $totalUsers);
            $courseCompletionRate = $this->calculateCourseCompletionRate($tenantId, $totalEnrollments);
            $activeUsers = $this->getActiveUsersCount($tenantId);
            $pendingEnrollments = $this->getPendingEnrollments($tenantId);

            return new DashboardStatsDTO(
                totalUsers: $totalUsers,
                totalCourses: $totalCourses,
                totalEnrollments: $totalEnrollments,
                totalRevenue: $totalRevenue,
                userGrowthRate: round($userGrowthRate, 1),
                courseCompletionRate: round($courseCompletionRate, 1),
                activeUsers: $activeUsers,
                pendingEnrollments: $pendingEnrollments
            );
        });
    }

    /**
     * Get recent activities for a tenant with pagination
     */
    public function getRecentActivities(string $tenantId, int $page = 1, int $perPage = 10): LengthAwarePaginator
    {
        $cacheKey = "recent_activities_{$tenantId}_{$page}_{$perPage}";

        return Cache::remember($cacheKey, 300, function () use ($tenantId, $page, $perPage) {
            $activities = collect();

            // Get recent enrollments
            $enrollments = $this->getRecentEnrollments($tenantId, 20);
            $activities = $activities->merge($enrollments);

            // Get recent completions
            $completions = $this->getRecentCompletions($tenantId, 20);
            $activities = $activities->merge($completions);

            // Get recent payments
            $payments = $this->getRecentPayments($tenantId, 20);
            $activities = $activities->merge($payments);

            // Sort all activities by timestamp
            $sortedActivities = $activities->sortByDesc('timestamp');

            // Get total count
            $total = $sortedActivities->count();

            // Calculate offset
            $offset = ($page - 1) * $perPage;

            // Get paginated items
            $items = $sortedActivities->slice($offset, $perPage)->values();

            // Create paginator
            return new LengthAwarePaginator(
                $items,
                $total,
                $perPage,
                $page,
                [
                    'path' => request()->url(),
                    'pageName' => 'page',
                ]
            );
        });
    }

    /**
     * Get course progress data for a tenant with pagination
     */
    public function getCourseProgress(string $tenantId, int $page = 1, int $perPage = 10): LengthAwarePaginator
    {
        $cacheKey = "course_progress_{$tenantId}_{$page}_{$perPage}";

        return Cache::remember($cacheKey, 600, function () use ($tenantId, $page, $perPage) {
            // Get total count first
            $total = Course::where('tenant_id', $tenantId)->count();

            // Calculate offset
            $offset = ($page - 1) * $perPage;

            // Get paginated courses with optimized queries
            $courses = Course::where('tenant_id', $tenantId)
                ->with([
                    'users' => function ($query) {
                        $query->select('users.id', 'users.name')->withPivot('role');
                    }
                ])
                ->skip($offset)
                ->take($perPage)
                ->get();

            // Get all course IDs for bulk queries
            $courseIds = $courses->pluck('id');

            // Bulk query for enrollments
            $enrollments = DB::table('course_user')
                ->select('course_id', DB::raw('COUNT(*) as count'))
                ->whereIn('course_id', $courseIds)
                ->where('role', 'student')
                ->groupBy('course_id')
                ->pluck('count', 'course_id');

            // Bulk query for completions
            $completions = StudentProgress::whereIn('course_id', $courseIds)
                ->where('completion_percentage', 100)
                ->select('course_id', DB::raw('COUNT(*) as count'))
                ->groupBy('course_id')
                ->pluck('count', 'course_id');

            // Bulk query for average progress
            $averageProgress = StudentProgress::whereIn('course_id', $courseIds)
                ->select('course_id', DB::raw('AVG(completion_percentage) as avg_progress'))
                ->groupBy('course_id')
                ->pluck('avg_progress', 'course_id');

            $items = $courses->map(function ($course) use ($enrollments, $completions, $averageProgress) {
                // Get counts from bulk queries
                $enrollmentCount = $enrollments->get($course->id, 0);
                $completionCount = $completions->get($course->id, 0);
                $avgProgress = $averageProgress->get($course->id, 0);

                // Calculate completion rate
                $completionRate = $enrollmentCount > 0
                    ? ($completionCount / $enrollmentCount) * 100
                    : 0;

                // Get instructor from pivot (first match)
                $instructor = optional(
                    $course->users->firstWhere('pivot.role', 'instructor')
                )->name ?? 'Unknown';

                // Return as DTO
                return new CourseProgressDTO(
                    id: $course->id,
                    title: $course->title,
                    enrollments: $enrollmentCount,
                    completions: $completionCount,
                    completionRate: round($completionRate, 1),
                    averageProgress: round($avgProgress, 1),
                    instructor: $instructor,
                    status: $course->is_active ? 'active' : 'inactive'
                );
            });

            return new LengthAwarePaginator(
                $items,
                $total,
                $perPage,
                $page,
                [
                    'path' => request()->url(),
                    'pageName' => 'page',
                ]
            );
        });
    }



    /**
     * Get user progress data for a tenant with pagination
     */
    public function getUserProgress(string $tenantId, int $page = 1, int $perPage = 10): LengthAwarePaginator
    {
        $cacheKey = "user_progress_{$tenantId}_{$page}_{$perPage}";

        return Cache::remember($cacheKey, 600, function () use ($tenantId, $page, $perPage) {
            // Get total count first
            $total = User::where('tenant_id', $tenantId)
                ->where('role', '!=', 'super_admin')
                ->count();

            // Calculate offset
            $offset = ($page - 1) * $perPage;

            // Get paginated users
            $users = User::where('tenant_id', $tenantId)
                ->where('role', '!=', 'super_admin')
                ->skip($offset)
                ->take($perPage)
                ->get();

            $userIds = $users->pluck('id');

            // Bulk query for user enrollment counts
            $enrollmentCounts = DB::table('course_user')
                ->join('courses', 'course_user.course_id', '=', 'courses.id')
                ->select('course_user.user_id', DB::raw('COUNT(*) as count'))
                ->where('courses.tenant_id', $tenantId)
                ->whereIn('course_user.user_id', $userIds)
                ->where('course_user.role', 'student')
                ->groupBy('course_user.user_id')
                ->pluck('count', 'user_id');

            // Bulk query for user completed courses
            $completedCounts = StudentProgress::whereIn('user_id', $userIds)
                ->where('completion_percentage', 100)
                ->select('user_id', DB::raw('COUNT(*) as count'))
                ->groupBy('user_id')
                ->pluck('count', 'user_id');

            // Bulk query for user average progress
            $averageProgress = StudentProgress::whereIn('user_id', $userIds)
                ->select('user_id', DB::raw('AVG(completion_percentage) as avg_progress'))
                ->groupBy('user_id')
                ->pluck('avg_progress', 'user_id');

            $items = $users->map(function ($user) use ($enrollmentCounts, $completedCounts, $averageProgress) {
                $enrollments = $enrollmentCounts->get($user->id, 0);
                $completedCourses = $completedCounts->get($user->id, 0);
                $totalProgress = $averageProgress->get($user->id, 0);

                return new UserProgressDTO(
                    id: $user->id,
                    name: $user->name,
                    email: $user->email,
                    avatar: '/avatars/default.png',
                    enrolledCourses: $enrollments,
                    completedCourses: $completedCourses,
                    totalProgress: round($totalProgress, 1),
                    lastActivity: $user->updated_at->diffForHumans(),
                    role: $user->role
                );
            });

            return new LengthAwarePaginator(
                $items,
                $total,
                $perPage,
                $page,
                [
                    'path' => request()->url(),
                    'pageName' => 'page',
                ]
            );
        });
    }

    /**
     * Get payment statistics for a tenant
     */
    public function getPaymentStats(string $tenantId): PaymentStatsDTO
    {
        return Cache::remember("payment_stats_{$tenantId}", 600, function () use ($tenantId) {
            $totalRevenue = $this->getTotalRevenue($tenantId);
            $monthlyRevenue = $this->getMonthlyRevenue($tenantId);
            $pendingPayments = $this->getPendingPaymentsCount($tenantId);
            $successfulPayments = $this->getSuccessfulPaymentsCount($tenantId);
            $averageOrderValue = $this->getAverageOrderValue($tenantId);
            $revenueGrowth = $this->calculateRevenueGrowth($tenantId, $monthlyRevenue);

            return new PaymentStatsDTO(
                totalRevenue: $totalRevenue,
                monthlyRevenue: $monthlyRevenue,
                pendingPayments: $pendingPayments,
                successfulPayments: $successfulPayments,
                failedPayments: 0, // Not tracked in current schema
                averageOrderValue: round($averageOrderValue, 2),
                revenueGrowth: round($revenueGrowth, 1)
            );
        });
    }

    /**
     * Get chart data for dashboard visualizations
     */
    public function getChartData(string $tenantId): ChartDataDTO
    {
        return Cache::remember("chart_data_{$tenantId}", 900, function () use ($tenantId) {
            $enrollmentTrends = $this->getEnrollmentTrends($tenantId);
            $completionTrends = $this->getCompletionTrends($tenantId);
            $revenueTrends = $this->getRevenueTrends($tenantId);
            $categoryDistribution = $this->getCategoryDistribution($tenantId);
            $userActivityTrends = $this->getUserActivityTrends($tenantId);
            $monthlyStats = $this->getMonthlyStats($tenantId);

            return new ChartDataDTO(
                enrollmentTrends: $enrollmentTrends,
                completionTrends: $completionTrends,
                revenueTrends: $revenueTrends,
                categoryDistribution: $categoryDistribution,
                userActivityTrends: $userActivityTrends,
                monthlyStats: $monthlyStats
            );
        });
    }

    // Private helper methods

    private function getTotalEnrollments(string $tenantId): int
    {
        return DB::table('course_user')
            ->join('courses', 'course_user.course_id', '=', 'courses.id')
            ->where('courses.tenant_id', $tenantId)
            ->where('course_user.role', 'student')
            ->count();
    }

    private function getTotalRevenue(string $tenantId): float
    {
        return CoursePurchase::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->sum('amount_paid');
    }

    private function calculateUserGrowthRate(string $tenantId, int $totalUsers): float
    {
        $lastMonth = Carbon::now()->subMonth();
        $usersLastMonth = User::where('tenant_id', $tenantId)
            ->where('created_at', '>=', $lastMonth)
            ->count();

        return $totalUsers > 0 ? ($usersLastMonth / $totalUsers) * 100 : 0;
    }

    private function calculateCourseCompletionRate(string $tenantId, int $totalEnrollments): float
    {
        $completedCourses = StudentProgress::where('tenant_id', $tenantId)
            ->where('completion_percentage', 100)
            ->count();

        return $totalEnrollments > 0 ? ($completedCourses / $totalEnrollments) * 100 : 0;
    }

    private function getActiveUsersCount(string $tenantId): int
    {
        return User::where('tenant_id', $tenantId)
            ->where('updated_at', '>=', Carbon::now()->subDays(30))
            ->count();
    }

    private function getPendingEnrollments(string $tenantId): int
    {
        return StudentProgress::where('tenant_id', $tenantId)
            ->where('completion_percentage', 0)
            ->count();
    }

    private function getRecentEnrollments(string $tenantId, int $limit = 10): Collection
    {
        return DB::table('course_user')
            ->join('courses', 'course_user.course_id', '=', 'courses.id')
            ->join('users', 'course_user.user_id', '=', 'users.id')
            ->select([
                'course_user.id',
                'course_user.created_at',
                'course_user.course_id',
                'courses.title',
                'users.name',
                'users.email'
            ])
            ->where('courses.tenant_id', $tenantId)
            ->where('course_user.role', 'student')
            ->where('course_user.created_at', '>=', Carbon::now()->subDays(7))
            ->orderBy('course_user.created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($enrollment) {
                return new ActivityDTO(
                    id: (string) $enrollment->id,
                    type: 'enrollment',
                    message: $enrollment->name . ' enrolled in ' . $enrollment->title,
                    timestamp: Carbon::parse($enrollment->created_at)->diffForHumans(),
                    user: new UserDTO(
                        name: $enrollment->name,
                        email: $enrollment->email,
                        avatar: '/avatars/default.png'
                    ),
                    metadata: [
                        'course_id' => $enrollment->course_id,
                        'course_title' => $enrollment->title,
                    ]
                );
            });
    }

    private function getRecentCompletions(string $tenantId, int $limit = 10): Collection
    {
        return StudentProgress::with(['user:id,name,email', 'course:id,title'])
            ->where('tenant_id', $tenantId)
            ->where('completion_percentage', 100)
            ->whereNotNull('completed_at')
            ->where('completed_at', '>=', Carbon::now()->subDays(7))
            ->orderBy('completed_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($completion) {
                return new ActivityDTO(
                    id: 'completion_' . $completion->id,
                    type: 'completion',
                    message: $completion->user->name . ' completed ' . $completion->course->title,
                    timestamp: $completion->completed_at->diffForHumans(),
                    user: new UserDTO(
                        name: $completion->user->name,
                        email: $completion->user->email,
                        avatar: '/avatars/default.png'
                    ),
                    metadata: [
                        'course_id' => $completion->course_id,
                        'course_title' => $completion->course->title,
                    ]
                );
            });
    }

    private function getRecentPayments(string $tenantId, int $limit = 10): Collection
    {
        return CoursePurchase::with(['student:id,name,email', 'course:id,title'])
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->where('created_at', '>=', Carbon::now()->subDays(7))
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($payment) {
                return new ActivityDTO(
                    id: 'payment_' . $payment->id,
                    type: 'payment',
                    message: $payment->student->name . ' made a payment of ' . $payment->currency . ' ' . number_format($payment->amount_paid, 2),
                    timestamp: $payment->created_at->diffForHumans(),
                    user: new UserDTO(
                        name: $payment->student->name,
                        email: $payment->student->email,
                        avatar: '/avatars/default.png'
                    ),
                    metadata: [
                        'course_id' => $payment->course_id,
                        'course_title' => $payment->course->title,
                        'amount' => $payment->amount_paid,
                    ]
                );
            });
    }

    private function getUserEnrollmentCount(int $userId, string $tenantId): int
    {
        return DB::table('course_user')
            ->join('courses', 'course_user.course_id', '=', 'courses.id')
            ->where('courses.tenant_id', $tenantId)
            ->where('course_user.user_id', $userId)
            ->where('course_user.role', 'student')
            ->count();
    }

    private function getUserCompletedCoursesCount(int $userId): int
    {
        return StudentProgress::where('user_id', $userId)
            ->where('completion_percentage', 100)
            ->count();
    }

    private function getUserTotalProgress(int $userId): float
    {
        return StudentProgress::where('user_id', $userId)
            ->avg('completion_percentage') ?? 0;
    }

    private function getMonthlyRevenue(string $tenantId): float
    {
        return CoursePurchase::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->where('created_at', '>=', Carbon::now()->startOfMonth())
            ->sum('amount_paid');
    }

    private function getPendingPaymentsCount(string $tenantId): int
    {
        return CoursePurchase::where('tenant_id', $tenantId)
            ->where('is_active', false)
            ->count();
    }

    private function getSuccessfulPaymentsCount(string $tenantId): int
    {
        return CoursePurchase::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->count();
    }

    private function getAverageOrderValue(string $tenantId): float
    {
        return CoursePurchase::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->avg('amount_paid') ?? 0;
    }

    private function calculateRevenueGrowth(string $tenantId, float $monthlyRevenue): float
    {
        $lastMonthRevenue = CoursePurchase::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->where('created_at', '>=', Carbon::now()->subMonth()->startOfMonth())
            ->where('created_at', '<=', Carbon::now()->subMonth()->endOfMonth())
            ->sum('amount_paid');

        return $lastMonthRevenue > 0 ? (($monthlyRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100 : 0;
    }

    // Chart Data Helper Methods

    private function getEnrollmentTrends(string $tenantId): array
    {
        $trends = DB::table('course_user')
            ->join('courses', 'course_user.course_id', '=', 'courses.id')
            ->where('courses.tenant_id', $tenantId)
            ->where('course_user.role', 'student')
            ->where('course_user.created_at', '>=', Carbon::now()->subDays(90)) // Extended to 90 days
            ->selectRaw('DATE(course_user.created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function ($item) {
                return [
                    'date' => Carbon::parse($item->date)->format('Y-m-d'),
                    'count' => $item->count
                ];
            })
            ->toArray();

        // If no data found, create sample data for the last 30 days
        if (empty($trends)) {
            $sampleData = [];
            for ($i = 29; $i >= 0; $i--) {
                $date = Carbon::now()->subDays($i)->format('Y-m-d');
                $sampleData[] = [
                    'date' => $date,
                    'count' => rand(0, 5) // Random sample data
                ];
            }
            return $sampleData;
        }

        return $trends;
    }

    private function getCompletionTrends(string $tenantId): array
    {
        return StudentProgress::where('tenant_id', $tenantId)
            ->where('completion_percentage', 100)
            ->whereNotNull('completed_at')
            ->where('completed_at', '>=', Carbon::now()->subDays(30))
            ->selectRaw('DATE(completed_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function ($item) {
                return [
                    'date' => Carbon::parse($item->date)->format('Y-m-d'),
                    'count' => $item->count
                ];
            })
            ->toArray();
    }

    private function getRevenueTrends(string $tenantId): array
    {
        return CoursePurchase::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->where('created_at', '>=', Carbon::now()->subDays(30))
            ->selectRaw('DATE(created_at) as date, SUM(amount_paid) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function ($item) {
                return [
                    'date' => Carbon::parse($item->date)->format('Y-m-d'),
                    'total' => (float) $item->total
                ];
            })
            ->toArray();
    }

    private function getCategoryDistribution(string $tenantId): array
    {
        return DB::table('courses')
            ->join('categories', 'courses.category_id', '=', 'categories.id')
            ->where('courses.tenant_id', $tenantId)
            ->selectRaw('categories.name as category, COUNT(*) as count')
            ->groupBy('categories.id', 'categories.name')
            ->orderBy('count', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'category' => $item->category,
                    'count' => $item->count
                ];
            })
            ->toArray();
    }

    private function getUserActivityTrends(string $tenantId): array
    {
        return User::where('tenant_id', $tenantId)
            ->where('role', '!=', 'super_admin')
            ->where('updated_at', '>=', Carbon::now()->subDays(30))
            ->selectRaw('DATE(updated_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function ($item) {
                return [
                    'date' => Carbon::parse($item->date)->format('Y-m-d'),
                    'count' => $item->count
                ];
            })
            ->toArray();
    }

    private function getMonthlyStats(string $tenantId): array
    {
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $enrollments = DB::table('course_user')
            ->join('courses', 'course_user.course_id', '=', 'courses.id')
            ->where('courses.tenant_id', $tenantId)
            ->where('course_user.role', 'student')
            ->whereBetween('course_user.created_at', [$startOfMonth, $endOfMonth])
            ->count();

        $completions = StudentProgress::where('tenant_id', $tenantId)
            ->where('completion_percentage', 100)
            ->whereNotNull('completed_at')
            ->whereBetween('completed_at', [$startOfMonth, $endOfMonth])
            ->count();

        $revenue = CoursePurchase::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->sum('amount_paid');

        return [
            'enrollments' => $enrollments,
            'completions' => $completions,
            'revenue' => (float) $revenue,
            'month' => $startOfMonth->format('F Y')
        ];
    }

    /**
     * Get users for management with filtering and pagination
     */
    public function getUsersForManagement(string $tenantId, int $page = 1, int $perPage = 15, ?string $search = null, ?string $role = null, ?string $status = null): LengthAwarePaginator
    {
        $query = User::where('tenant_id', $tenantId);

        // Apply search filter
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%');
            });
        }

        // Apply role filter
        if ($role) {
            $query->where('role', $role);
        }

        // Apply status filter (for now we'll skip this since status column may not exist)

        $users = $query->latest()->paginate($perPage, ['*'], 'page', $page);
        $userIds = $users->pluck('id');

        // Bulk query for enrollments
        $enrollmentCounts = DB::table('course_user')
            ->join('courses', 'course_user.course_id', '=', 'courses.id')
            ->select('course_user.user_id', DB::raw('COUNT(*) as count'))
            ->where('courses.tenant_id', $tenantId)
            ->whereIn('course_user.user_id', $userIds)
            ->where('course_user.role', 'student')
            ->groupBy('course_user.user_id')
            ->pluck('count', 'user_id');

        // Bulk query for completions
        $completionCounts = StudentProgress::whereIn('user_id', $userIds)
            ->where('completion_percentage', 100)
            ->select('user_id', DB::raw('COUNT(*) as count'))
            ->groupBy('user_id')
            ->pluck('count', 'user_id');

        // Bulk query for average progress
        $averageProgress = StudentProgress::whereIn('user_id', $userIds)
            ->select('user_id', DB::raw('AVG(completion_percentage) as avg_progress'))
            ->groupBy('user_id')
            ->pluck('avg_progress', 'user_id');

        $users->getCollection()->transform(function ($user) use ($enrollmentCounts, $completionCounts, $averageProgress) {
            $enrollments = $enrollmentCounts->get($user->id, 0);
            $completions = $completionCounts->get($user->id, 0);
            $avgProgress = $averageProgress->get($user->id, 0);

            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role ?? 'student',
                'status' => 'active', // Default status
                'enrolledCourses' => $enrollments,
                'completedCourses' => $completions,
                'completionRate' => $enrollments > 0 ? round(($completions / $enrollments) * 100, 1) : 0,
                'progressPercentage' => round($avgProgress, 1),
                'totalSpent' => 0, // TODO: Implement based on your payment model
                'lastLogin' => $user->updated_at?->toISOString() ?? $user->created_at->toISOString(),
                'joinedAt' => $user->created_at->toISOString(),
                'instructor' => $user->role ?? 'N/A',
            ];
        });

        return $users;
    }
    /**
     * Get user statistics for dashboard
     */
    public function getUserStats(string $tenantId): object
    {
        return Cache::remember("user_stats_{$tenantId}", 300, function () use ($tenantId) {
            $totalUsers = User::where('tenant_id', $tenantId)->count();
            $activeUsers = User::where('tenant_id', $tenantId)->count(); // All users are considered active for now

            $newUsersThisMonth = User::where('tenant_id', $tenantId)
                ->whereMonth('created_at', Carbon::now()->month)
                ->whereYear('created_at', Carbon::now()->year)
                ->count();

            // For now, we'll set total revenue to 0 since we don't have a payments table
            $totalRevenue = 0; // TODO: Implement based on your payment model

            $averageProgress = StudentProgress::where('tenant_id', $tenantId)
                ->avg('completion_percentage') ?? 0;

            // Get top performers based on completed courses and progress - limit to 10 to avoid performance issues
            $topUsersIds = User::where('tenant_id', $tenantId)
                ->limit(10)
                ->pluck('id');

            // Bulk queries for top performers
            $enrollmentCounts = DB::table('course_user')
                ->join('courses', 'course_user.course_id', '=', 'courses.id')
                ->select('course_user.user_id', DB::raw('COUNT(*) as count'))
                ->where('courses.tenant_id', $tenantId)
                ->whereIn('course_user.user_id', $topUsersIds)
                ->where('course_user.role', 'student')
                ->groupBy('course_user.user_id')
                ->pluck('count', 'user_id');

            $completionCounts = StudentProgress::whereIn('user_id', $topUsersIds)
                ->where('completion_percentage', 100)
                ->select('user_id', DB::raw('COUNT(*) as count'))
                ->groupBy('user_id')
                ->pluck('count', 'user_id');

            $progressAverages = StudentProgress::whereIn('user_id', $topUsersIds)
                ->select('user_id', DB::raw('AVG(completion_percentage) as avg_progress'))
                ->groupBy('user_id')
                ->pluck('avg_progress', 'user_id');

            $topPerformers = User::whereIn('id', $topUsersIds)
                ->get()
                ->map(function ($user) use ($enrollmentCounts, $completionCounts, $progressAverages) {
                    $enrollments = $enrollmentCounts->get($user->id, 0);
                    $completions = $completionCounts->get($user->id, 0);
                    $averageProgress = $progressAverages->get($user->id, 0);

                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'enrolledCourses' => $enrollments,
                        'completedCourses' => $completions,
                        'progressPercentage' => round($averageProgress, 1),
                        'totalSpent' => 0, // TODO: Implement based on your payment model
                    ];
                })
                ->sortByDesc('completedCourses')
                ->values();

            return (object) [
                'totalUsers' => $totalUsers,
                'activeUsers' => $activeUsers,
                'newUsersThisMonth' => $newUsersThisMonth,
                'totalRevenue' => $totalRevenue,
                'averageProgress' => round($averageProgress, 1),
                'topPerformers' => $topPerformers,
            ];
        });
    }

    /**
     * Get user activity feed
     */
    public function getUserActivityFeed(string $tenantId, int $page = 1, int $perPage = 10, ?int $userId = null): LengthAwarePaginator
    {
        $cacheKey = "user_activity_feed_{$tenantId}_{$page}_{$perPage}" . ($userId ? "_{$userId}" : '');

        return Cache::remember($cacheKey, 300, function () use ($tenantId, $page, $perPage, $userId) {
            // Create sample activity data since we don't have a dedicated activity table
            $activities = collect();

            // Get recent enrollments
            $enrollments = DB::table('course_user')
                ->join('users', 'course_user.user_id', '=', 'users.id')
                ->join('courses', 'course_user.course_id', '=', 'courses.id')
                ->where('users.tenant_id', $tenantId)
                ->where('course_user.role', 'student')
                ->when($userId, function ($query) use ($userId) {
                    return $query->where('users.id', $userId);
                })
                ->orderBy('course_user.created_at', 'desc')
                ->limit(20)
                ->get([
                    'course_user.id',
                    'users.id as user_id',
                    'users.name as user_name',
                    'courses.title as course_title',
                    'courses.id as course_id',
                    'course_user.created_at as timestamp'
                ])
                ->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'userId' => $item->user_id,
                        'activity' => "enrolled in course",
                        'courseName' => $item->course_title,
                        'courseId' => $item->course_id,
                        'timestamp' => $item->timestamp,
                    ];
                });

            $activities = $activities->merge($enrollments);

            // Get recent completions
            $completions = StudentProgress::where('tenant_id', $tenantId)
                ->where('completion_percentage', 100)
                ->whereNotNull('completed_at')
                ->with(['user', 'course'])
                ->when($userId, function ($query) use ($userId) {
                    return $query->where('user_id', $userId);
                })
                ->orderBy('completed_at', 'desc')
                ->limit(20)
                ->get()
                ->map(function ($progress) {
                    return [
                        'id' => $progress->id,
                        'userId' => $progress->user_id,
                        'activity' => "completed course",
                        'courseName' => $progress->course->title ?? 'Unknown Course',
                        'courseId' => $progress->course_id,
                        'timestamp' => $progress->completed_at,
                    ];
                });

            $activities = $activities->merge($completions);

            // Since we don't have a purchases table, let's create some sample purchase activities
            // You can remove this and add real purchase data when you implement the payment system
            if ($activities->count() < 10) {
                $sampleActivities = collect([
                    [
                        'id' => 1000,
                        'userId' => 1,
                        'activity' => "purchased course for $99.00",
                        'courseName' => 'Sample Course',
                        'courseId' => 1,
                        'timestamp' => Carbon::now()->subDays(1)->toISOString(),
                    ],
                    [
                        'id' => 1001,
                        'userId' => 2,
                        'activity' => "purchased course for $149.00",
                        'courseName' => 'Advanced Course',
                        'courseId' => 2,
                        'timestamp' => Carbon::now()->subDays(2)->toISOString(),
                    ]
                ]);
                $activities = $activities->merge($sampleActivities);
            }

            // Sort by timestamp and paginate
            $activities = $activities->sortByDesc('timestamp')->values();

            // Manual pagination
            $total = $activities->count();
            $items = $activities->slice(($page - 1) * $perPage, $perPage)->values();

            return new LengthAwarePaginator(
                $items,
                $total,
                $perPage,
                $page,
                ['path' => request()->url(), 'pageName' => 'page']
            );
        });
    }
}
