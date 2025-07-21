<?php

namespace App\Services\Cache;

use App\Models\User;
use App\Models\Course;
use App\Models\CoursePurchase;
use App\Models\Certificate;
use App\Models\Feedback;
use App\Models\StudentProgress;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardCache
{
    protected int $defaultTtl = 1800; // 30 minutes
    protected int $shortTtl = 300; // 5 minutes
    protected int $longTtl = 3600; // 1 hour

    /**
     * Get comprehensive dashboard statistics for a tenant
     */
    public function getDashboardStats(int $tenantId): array
    {
        $cacheKey = "dashboard_stats_{$tenantId}";
        
        return Cache::tags(["tenant_{$tenantId}"])->remember($cacheKey, $this->defaultTtl, function () use ($tenantId) {
            // Basic counts
            $totalUsers = User::where('tenant_id', $tenantId)->count();
            $totalCourses = Course::where('tenant_id', $tenantId)->count();
            $totalPurchases = CoursePurchase::whereHas('course', function ($query) use ($tenantId) {
                $query->where('tenant_id', $tenantId);
            })->count();
            $totalCertificates = Certificate::whereHas('course', function ($query) use ($tenantId) {
                $query->where('tenant_id', $tenantId);
            })->count();

            // Revenue statistics
            $totalRevenue = CoursePurchase::whereHas('course', function ($query) use ($tenantId) {
                $query->where('tenant_id', $tenantId);
            })->sum('amount');

            $monthlyRevenue = CoursePurchase::whereHas('course', function ($query) use ($tenantId) {
                $query->where('tenant_id', $tenantId);
            })->whereMonth('purchased_at', now()->month)
              ->whereYear('purchased_at', now()->year)
              ->sum('amount');

            // User role distribution
            $userRoles = User::where('tenant_id', $tenantId)
                ->select('role', DB::raw('count(*) as count'))
                ->groupBy('role')
                ->pluck('count', 'role')
                ->toArray();

            // Course enrollment statistics
            $courseEnrollments = DB::table('course_user')
                ->join('courses', 'course_user.course_id', '=', 'courses.id')
                ->where('courses.tenant_id', $tenantId)
                ->where('course_user.role', 'student')
                ->count();

            // Active users (users with progress in last 30 days)
            $activeUsers = User::where('tenant_id', $tenantId)
                ->whereHas('studentProgress', function ($query) {
                    $query->where('updated_at', '>=', now()->subDays(30));
                })
                ->count();

            // Average course completion rate
            $courseCompletionData = $this->getCourseCompletionRate($tenantId);

            return [
                'total_users' => $totalUsers,
                'total_courses' => $totalCourses,
                'total_purchases' => $totalPurchases,
                'total_certificates' => $totalCertificates,
                'total_revenue' => $totalRevenue,
                'monthly_revenue' => $monthlyRevenue,
                'user_roles' => $userRoles,
                'course_enrollments' => $courseEnrollments,
                'active_users' => $activeUsers,
                'course_completion_rate' => $courseCompletionData['completion_rate'],
                'courses_with_completions' => $courseCompletionData['courses_with_completions'],
                'average_course_rating' => $this->getAverageCourseRating($tenantId),
                'recent_activities' => $this->getRecentActivities($tenantId),
            ];
        });
    }

    /**
     * Get revenue analytics for a tenant
     */
    public function getRevenueAnalytics(int $tenantId): array
    {
        $cacheKey = "revenue_analytics_{$tenantId}";
        
        return Cache::tags(["tenant_{$tenantId}"])->remember($cacheKey, $this->defaultTtl, function () use ($tenantId) {
            // Daily revenue for last 30 days
            $dailyRevenue = CoursePurchase::whereHas('course', function ($query) use ($tenantId) {
                $query->where('tenant_id', $tenantId);
            })->where('purchased_at', '>=', now()->subDays(30))
              ->select(DB::raw('DATE(purchased_at) as date'), DB::raw('SUM(amount) as revenue'))
              ->groupBy('date')
              ->orderBy('date')
              ->get();

            // Monthly revenue for last 12 months
            $monthlyRevenue = CoursePurchase::whereHas('course', function ($query) use ($tenantId) {
                $query->where('tenant_id', $tenantId);
            })->where('purchased_at', '>=', now()->subMonths(12))
              ->select(DB::raw('YEAR(purchased_at) as year'), DB::raw('MONTH(purchased_at) as month'), DB::raw('SUM(amount) as revenue'))
              ->groupBy('year', 'month')
              ->orderBy('year', 'month')
              ->get();

            // Top selling courses
            $topCourses = Course::where('tenant_id', $tenantId)
                ->withCount('coursePurchases')
                ->with(['coursePurchases' => function ($query) {
                    $query->select('course_id', DB::raw('SUM(amount) as total_revenue'));
                }])
                ->orderBy('course_purchases_count', 'desc')
                ->limit(10)
                ->get();

            return [
                'daily_revenue' => $dailyRevenue,
                'monthly_revenue' => $monthlyRevenue,
                'top_selling_courses' => $topCourses,
                'total_transactions' => CoursePurchase::whereHas('course', function ($query) use ($tenantId) {
                    $query->where('tenant_id', $tenantId);
                })->count(),
                'average_transaction_value' => CoursePurchase::whereHas('course', function ($query) use ($tenantId) {
                    $query->where('tenant_id', $tenantId);
                })->avg('amount'),
            ];
        });
    }

    /**
     * Get user engagement metrics
     */
    public function getUserEngagementMetrics(int $tenantId): array
    {
        $cacheKey = "user_engagement_{$tenantId}";
        
        return Cache::tags(["tenant_{$tenantId}"])->remember($cacheKey, $this->defaultTtl, function () use ($tenantId) {
            // User activity over time
            $userActivity = StudentProgress::whereHas('user', function ($query) use ($tenantId) {
                $query->where('tenant_id', $tenantId);
            })->where('updated_at', '>=', now()->subDays(30))
              ->select(DB::raw('DATE(updated_at) as date'), DB::raw('COUNT(DISTINCT user_id) as active_users'))
              ->groupBy('date')
              ->orderBy('date')
              ->get();

            // Course completion trends
            $completionTrends = StudentProgress::whereHas('user', function ($query) use ($tenantId) {
                $query->where('tenant_id', $tenantId);
            })->where('status', 'completed')
              ->where('updated_at', '>=', now()->subDays(30))
              ->select(DB::raw('DATE(updated_at) as date'), DB::raw('COUNT(*) as completions'))
              ->groupBy('date')
              ->orderBy('date')
              ->get();

            // Average session duration
            $avgSessionDuration = StudentProgress::whereHas('user', function ($query) use ($tenantId) {
                $query->where('tenant_id', $tenantId);
            })->avg('time_spent');

            return [
                'user_activity' => $userActivity,
                'completion_trends' => $completionTrends,
                'average_session_duration' => $avgSessionDuration,
                'total_study_time' => StudentProgress::whereHas('user', function ($query) use ($tenantId) {
                    $query->where('tenant_id', $tenantId);
                })->sum('time_spent'),
            ];
        });
    }

    /**
     * Get course performance metrics
     */
    public function getCoursePerformanceMetrics(int $tenantId): array
    {
        $cacheKey = "course_performance_{$tenantId}";
        
        return Cache::tags(["tenant_{$tenantId}"])->remember($cacheKey, $this->shortTtl, function () use ($tenantId) {
            $courses = Course::where('tenant_id', $tenantId)
                ->with(['users', 'feedback', 'coursePurchases'])
                ->get();

            $performanceData = [];
            
            foreach ($courses as $course) {
                $enrollmentCount = $course->users()->wherePivot('role', 'student')->count();
                $completionCount = $this->getCourseCompletionCount($course->id);
                $averageRating = $course->feedback->avg('rating') ?? 0;
                $totalRevenue = $course->coursePurchases->sum('amount');
                
                $performanceData[] = [
                    'course_id' => $course->id,
                    'course_name' => $course->title,
                    'enrollment_count' => $enrollmentCount,
                    'completion_count' => $completionCount,
                    'completion_rate' => $enrollmentCount > 0 ? round(($completionCount / $enrollmentCount) * 100, 2) : 0,
                    'average_rating' => round($averageRating, 2),
                    'total_revenue' => $totalRevenue,
                    'feedback_count' => $course->feedback->count(),
                ];
            }

            return [
                'courses' => $performanceData,
                'top_performing_courses' => collect($performanceData)->sortByDesc('completion_rate')->take(5)->values(),
                'highest_rated_courses' => collect($performanceData)->sortByDesc('average_rating')->take(5)->values(),
                'highest_revenue_courses' => collect($performanceData)->sortByDesc('total_revenue')->take(5)->values(),
            ];
        });
    }

    /**
     * Get recent activities for dashboard
     */
    protected function getRecentActivities(int $tenantId): array
    {
        $recentProgress = StudentProgress::whereHas('user', function ($query) use ($tenantId) {
            $query->where('tenant_id', $tenantId);
        })->with(['user', 'courseContent.course'])
          ->orderBy('updated_at', 'desc')
          ->limit(10)
          ->get();

        $recentPurchases = CoursePurchase::whereHas('course', function ($query) use ($tenantId) {
            $query->where('tenant_id', $tenantId);
        })->with(['user', 'course'])
          ->orderBy('purchased_at', 'desc')
          ->limit(10)
          ->get();

        $recentCertificates = Certificate::whereHas('course', function ($query) use ($tenantId) {
            $query->where('tenant_id', $tenantId);
        })->with(['user', 'course'])
          ->orderBy('issued_at', 'desc')
          ->limit(10)
          ->get();

        return [
            'recent_progress' => $recentProgress,
            'recent_purchases' => $recentPurchases,
            'recent_certificates' => $recentCertificates,
        ];
    }

    /**
     * Get course completion rate for tenant
     */
    protected function getCourseCompletionRate(int $tenantId): array
    {
        $courses = Course::where('tenant_id', $tenantId)->get();
        $totalCourses = $courses->count();
        $coursesWithCompletions = 0;
        $totalCompletionRate = 0;

        foreach ($courses as $course) {
            $enrollmentCount = $course->users()->wherePivot('role', 'student')->count();
            if ($enrollmentCount > 0) {
                $completionCount = $this->getCourseCompletionCount($course->id);
                $courseCompletionRate = ($completionCount / $enrollmentCount) * 100;
                $totalCompletionRate += $courseCompletionRate;
                $coursesWithCompletions++;
            }
        }

        return [
            'completion_rate' => $coursesWithCompletions > 0 ? round($totalCompletionRate / $coursesWithCompletions, 2) : 0,
            'courses_with_completions' => $coursesWithCompletions,
        ];
    }

    /**
     * Get completion count for a specific course
     */
    protected function getCourseCompletionCount(int $courseId): int
    {
        return User::whereHas('courses', function ($query) use ($courseId) {
            $query->where('course_id', $courseId)->where('role', 'student');
        })->whereHas('studentProgress', function ($query) use ($courseId) {
            $query->whereHas('courseContent', function ($q) use ($courseId) {
                $q->where('course_id', $courseId);
            })->where('status', 'completed');
        })->count();
    }

    /**
     * Get average course rating for tenant
     */
    protected function getAverageCourseRating(int $tenantId): float
    {
        return Feedback::whereHas('course', function ($query) use ($tenantId) {
            $query->where('tenant_id', $tenantId);
        })->avg('rating') ?? 0;
    }

    /**
     * Clear all dashboard cache for a tenant
     */
    public function clearDashboardCache(int $tenantId): void
    {
        // Use tags to flush all tenant dashboard-related cache
        Cache::tags(["tenant_{$tenantId}"])->flush();
    }

    /**
     * Warm up dashboard cache
     */
    public function warmUpDashboardCache(int $tenantId): void
    {
        $this->getDashboardStats($tenantId);
        $this->getRevenueAnalytics($tenantId);
        $this->getUserEngagementMetrics($tenantId);
        $this->getCoursePerformanceMetrics($tenantId);
    }
}
