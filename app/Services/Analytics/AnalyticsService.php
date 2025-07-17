<?php

namespace App\Services\Analytics;

use App\Models\{Course, User, StudentProgress, CoursePurchase};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class AnalyticsService
{
    /**
     * Get analytics overview with key metrics
     */
    public function getAnalyticsOverview(string $tenantId, string $timeRange): array
    {
        $cacheKey = "analytics_overview_{$tenantId}_{$timeRange}";
        
        return Cache::remember($cacheKey, 300, function () use ($tenantId, $timeRange) {
            $dateRange = $this->getDateRange($timeRange);
            
            return [
                'key_metrics' => [
                    'total_users' => $this->getTotalUsers($tenantId),
                    'active_users' => $this->getActiveUsers($tenantId, $dateRange),
                    'total_courses' => $this->getTotalCourses($tenantId),
                    'total_enrollments' => $this->getTotalEnrollments($tenantId),
                    'completion_rate' => $this->getCompletionRate($tenantId),
                    'average_progress' => $this->getAverageProgress($tenantId),
                    'total_revenue' => 0, // TODO: Implement when payment system is ready
                ],
                'growth_metrics' => [
                    'user_growth' => $this->getUserGrowth($tenantId, $dateRange),
                    'enrollment_growth' => $this->getEnrollmentGrowth($tenantId, $dateRange),
                    'completion_growth' => $this->getCompletionGrowth($tenantId, $dateRange),
                ],
                'time_range' => $timeRange,
                'last_updated' => now()->toISOString(),
            ];
        });
    }

    /**
     * Get engagement metrics
     */
    public function getEngagementMetrics(string $tenantId, string $timeRange): array
    {
        $cacheKey = "engagement_metrics_{$tenantId}_{$timeRange}";
        
        return Cache::remember($cacheKey, 300, function () use ($tenantId, $timeRange) {
            $dateRange = $this->getDateRange($timeRange);
            
            return [
                'daily_active_users' => $this->getDailyActiveUsers($tenantId, $dateRange),
                'session_duration' => $this->getAverageSessionDuration($tenantId, $dateRange),
                'course_interaction_rate' => $this->getCourseInteractionRate($tenantId, $dateRange),
                'popular_courses' => $this->getPopularCourses($tenantId, $dateRange),
                'engagement_trends' => $this->getEngagementTrends($tenantId, $dateRange),
            ];
        });
    }

    /**
     * Get performance metrics
     */
    public function getPerformanceMetrics(string $tenantId, string $timeRange): array
    {
        $cacheKey = "performance_metrics_{$tenantId}_{$timeRange}";
        
        return Cache::remember($cacheKey, 300, function () use ($tenantId, $timeRange) {
            $dateRange = $this->getDateRange($timeRange);
            
            return [
                'completion_rates' => $this->getCompletionRatesByPeriod($tenantId, $dateRange),
                'average_completion_time' => $this->getAverageCompletionTime($tenantId, $dateRange),
                'top_performing_courses' => $this->getTopPerformingCourses($tenantId, $dateRange),
                'struggling_students' => $this->getStrugglingStudents($tenantId, $dateRange),
                'performance_distribution' => $this->getPerformanceDistribution($tenantId, $dateRange),
            ];
        });
    }

    /**
     * Get trend analysis
     */
    public function getTrendAnalysis(string $tenantId, string $timeRange, string $metric): array
    {
        $cacheKey = "trends_{$tenantId}_{$timeRange}_{$metric}";
        
        return Cache::remember($cacheKey, 300, function () use ($tenantId, $timeRange, $metric) {
            $dateRange = $this->getDateRange($timeRange);
            
            $trends = [];
            
            if ($metric === 'all' || $metric === 'users') {
                $trends['user_trends'] = $this->getUserTrends($tenantId, $dateRange);
            }
            
            if ($metric === 'all' || $metric === 'courses') {
                $trends['course_trends'] = $this->getCourseTrends($tenantId, $dateRange);
            }
            
            if ($metric === 'all' || $metric === 'engagement') {
                $trends['engagement_trends'] = $this->getEngagementTrends($tenantId, $dateRange);
            }
            
            if ($metric === 'all' || $metric === 'revenue') {
                $trends['revenue_trends'] = $this->getRevenueTrends($tenantId, $dateRange);
            }
            
            return $trends;
        });
    }

    /**
     * Get user behavior analytics
     */
    public function getUserBehaviorAnalytics(string $tenantId, string $timeRange): array
    {
        $cacheKey = "user_behavior_{$tenantId}_{$timeRange}";
        
        return Cache::remember($cacheKey, 300, function () use ($tenantId, $timeRange) {
            $dateRange = $this->getDateRange($timeRange);
            
            return [
                'user_segments' => $this->getUserSegments($tenantId, $dateRange),
                'learning_patterns' => $this->getLearningPatterns($tenantId, $dateRange),
                'device_usage' => $this->getDeviceUsage($tenantId, $dateRange),
                'peak_learning_hours' => $this->getPeakLearningHours($tenantId, $dateRange),
                'course_progression' => $this->getCourseProgression($tenantId, $dateRange),
            ];
        });
    }

    /**
     * Get course analytics
     */
    public function getCourseAnalytics(string $tenantId, string $timeRange): array
    {
        $cacheKey = "course_analytics_{$tenantId}_{$timeRange}";
        
        return Cache::remember($cacheKey, 300, function () use ($tenantId, $timeRange) {
            $dateRange = $this->getDateRange($timeRange);
            
            return [
                'course_performance' => $this->getCoursePerformanceMetrics($tenantId, $dateRange),
                'enrollment_patterns' => $this->getEnrollmentPatterns($tenantId, $dateRange),
                'completion_funnel' => $this->getCompletionFunnel($tenantId, $dateRange),
                'content_effectiveness' => $this->getContentEffectiveness($tenantId, $dateRange),
                'drop_off_points' => $this->getDropOffPoints($tenantId, $dateRange),
            ];
        });
    }

    /**
     * Get revenue analytics
     */
    public function getRevenueAnalytics(string $tenantId, string $timeRange): array
    {
        $cacheKey = "revenue_analytics_{$tenantId}_{$timeRange}";
        
        return Cache::remember($cacheKey, 300, function () use ($tenantId, $timeRange) {
            // TODO: Implement when payment system is ready
            return [
                'total_revenue' => 0,
                'revenue_trends' => [],
                'revenue_by_course' => [],
                'average_order_value' => 0,
                'conversion_rate' => 0,
                'refund_rate' => 0,
            ];
        });
    }

    /**
     * Get retention metrics
     */
    public function getRetentionMetrics(string $tenantId, string $timeRange): array
    {
        $cacheKey = "retention_metrics_{$tenantId}_{$timeRange}";
        
        return Cache::remember($cacheKey, 300, function () use ($tenantId, $timeRange) {
            $dateRange = $this->getDateRange($timeRange);
            
            return [
                'user_retention' => $this->getUserRetention($tenantId, $dateRange),
                'course_retention' => $this->getCourseRetention($tenantId, $dateRange),
                'churn_rate' => $this->getChurnRate($tenantId, $dateRange),
                'lifetime_value' => $this->getLifetimeValue($tenantId, $dateRange),
            ];
        });
    }

    // Helper methods

    private function getDateRange(string $timeRange): array
    {
        $endDate = Carbon::now();
        
        switch ($timeRange) {
            case '7d':
                $startDate = $endDate->copy()->subDays(7);
                break;
            case '30d':
                $startDate = $endDate->copy()->subDays(30);
                break;
            case '90d':
                $startDate = $endDate->copy()->subDays(90);
                break;
            case '1y':
                $startDate = $endDate->copy()->subYear();
                break;
            default:
                $startDate = $endDate->copy()->subDays(30);
        }
        
        return [$startDate, $endDate];
    }

    private function getTotalUsers(string $tenantId): int
    {
        return User::where('tenant_id', $tenantId)->count();
    }

    private function getActiveUsers(string $tenantId, array $dateRange): int
    {
        // For now, consider all users as active (you can implement last_login tracking)
        return User::where('tenant_id', $tenantId)->count();
    }

    private function getTotalCourses(string $tenantId): int
    {
        return Course::where('tenant_id', $tenantId)->count();
    }

    private function getTotalEnrollments(string $tenantId): int
    {
        return DB::table('course_user')
            ->join('courses', 'course_user.course_id', '=', 'courses.id')
            ->where('courses.tenant_id', $tenantId)
            ->where('course_user.role', 'student')
            ->count();
    }

    private function getCompletionRate(string $tenantId): float
    {
        $totalEnrollments = $this->getTotalEnrollments($tenantId);
        if ($totalEnrollments === 0) return 0;

        $completedCourses = StudentProgress::where('tenant_id', $tenantId)
            ->where('completion_percentage', 100)
            ->count();

        return round(($completedCourses / $totalEnrollments) * 100, 2);
    }

    private function getAverageProgress(string $tenantId): float
    {
        return round(
            StudentProgress::where('tenant_id', $tenantId)
                ->avg('completion_percentage') ?? 0,
            2
        );
    }

    private function getUserGrowth(string $tenantId, array $dateRange): array
    {
        [$startDate, $endDate] = $dateRange;
        
        $currentPeriod = User::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();
            
        $previousPeriod = User::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [
                $startDate->copy()->subDays($endDate->diffInDays($startDate)),
                $startDate
            ])
            ->count();
            
        $growthRate = $previousPeriod > 0 
            ? round((($currentPeriod - $previousPeriod) / $previousPeriod) * 100, 2)
            : 0;
            
        return [
            'current_period' => $currentPeriod,
            'previous_period' => $previousPeriod,
            'growth_rate' => $growthRate,
        ];
    }

    private function getEnrollmentGrowth(string $tenantId, array $dateRange): array
    {
        [$startDate, $endDate] = $dateRange;
        
        $currentPeriod = DB::table('course_user')
            ->join('courses', 'course_user.course_id', '=', 'courses.id')
            ->where('courses.tenant_id', $tenantId)
            ->where('course_user.role', 'student')
            ->whereBetween('course_user.created_at', [$startDate, $endDate])
            ->count();
            
        $previousPeriod = DB::table('course_user')
            ->join('courses', 'course_user.course_id', '=', 'courses.id')
            ->where('courses.tenant_id', $tenantId)
            ->where('course_user.role', 'student')
            ->whereBetween('course_user.created_at', [
                $startDate->copy()->subDays($endDate->diffInDays($startDate)),
                $startDate
            ])
            ->count();
            
        $growthRate = $previousPeriod > 0 
            ? round((($currentPeriod - $previousPeriod) / $previousPeriod) * 100, 2)
            : 0;
            
        return [
            'current_period' => $currentPeriod,
            'previous_period' => $previousPeriod,
            'growth_rate' => $growthRate,
        ];
    }

    private function getCompletionGrowth(string $tenantId, array $dateRange): array
    {
        [$startDate, $endDate] = $dateRange;
        
        $currentPeriod = StudentProgress::where('tenant_id', $tenantId)
            ->where('completion_percentage', 100)
            ->whereBetween('updated_at', [$startDate, $endDate])
            ->count();
            
        $previousPeriod = StudentProgress::where('tenant_id', $tenantId)
            ->where('completion_percentage', 100)
            ->whereBetween('updated_at', [
                $startDate->copy()->subDays($endDate->diffInDays($startDate)),
                $startDate
            ])
            ->count();
            
        $growthRate = $previousPeriod > 0 
            ? round((($currentPeriod - $previousPeriod) / $previousPeriod) * 100, 2)
            : 0;
            
        return [
            'current_period' => $currentPeriod,
            'previous_period' => $previousPeriod,
            'growth_rate' => $growthRate,
        ];
    }

    private function getDailyActiveUsers(string $tenantId, array $dateRange): array
    {
        // Placeholder - implement when you have user activity tracking
        return [];
    }

    private function getAverageSessionDuration(string $tenantId, array $dateRange): float
    {
        // Placeholder - implement when you have session tracking
        return 0;
    }

    private function getCourseInteractionRate(string $tenantId, array $dateRange): float
    {
        // Placeholder - implement when you have interaction tracking
        return 0;
    }

    private function getPopularCourses(string $tenantId, array $dateRange): array
    {
        return Course::where('tenant_id', $tenantId)
            ->withCount(['students' => function ($query) {
                $query->where('course_user.role', 'student');
            }])
            ->orderBy('students_count', 'desc')
            ->limit(10)
            ->get(['id', 'title', 'students_count'])
            ->toArray();
    }

    private function getEngagementTrends(string $tenantId, array $dateRange): array
    {
        // Placeholder for engagement trends
        return [];
    }

    private function getCompletionRatesByPeriod(string $tenantId, array $dateRange): array
    {
        [$startDate, $endDate] = $dateRange;
        
        // Get completion rates by week for the specified period
        $completionRates = [];
        $current = $startDate->copy();
        
        while ($current->lte($endDate)) {
            $weekEnd = $current->copy()->addDays(6);
            if ($weekEnd->gt($endDate)) {
                $weekEnd = $endDate->copy();
            }
            
            $totalEnrollments = DB::table('course_user')
                ->join('courses', 'course_user.course_id', '=', 'courses.id')
                ->where('courses.tenant_id', $tenantId)
                ->where('course_user.role', 'student')
                ->whereBetween('course_user.created_at', [$current, $weekEnd])
                ->count();
                
            $completedCourses = StudentProgress::where('tenant_id', $tenantId)
                ->where('completion_percentage', 100)
                ->whereBetween('created_at', [$current, $weekEnd])
                ->count();
                
            $completionRate = $totalEnrollments > 0 
                ? round(($completedCourses / $totalEnrollments) * 100, 2)
                : 0;
                
            $completionRates[] = [
                'period' => $current->format('M d'),
                'completion_rate' => $completionRate,
                'total_enrollments' => $totalEnrollments,
                'completed_courses' => $completedCourses,
            ];
            
            $current->addDays(7);
        }
        
        return $completionRates;
    }

    private function getAverageCompletionTime(string $tenantId, array $dateRange): float
    {
        [$startDate, $endDate] = $dateRange;
        
        // Calculate average time from enrollment to completion
        $completedProgresses = DB::table('student_progress as sp')
            ->join('course_user as cu', function($join) {
                $join->on('sp.user_id', '=', 'cu.user_id')
                     ->on('sp.course_id', '=', 'cu.course_id');
            })
            ->join('courses as c', 'sp.course_id', '=', 'c.id')
            ->where('c.tenant_id', $tenantId)
            ->where('sp.completion_percentage', 100)
            ->whereBetween('sp.updated_at', [$startDate, $endDate])
            ->select([
                'cu.created_at as enrollment_date',
                'sp.updated_at as completion_date'
            ])
            ->get();
            
        if ($completedProgresses->isEmpty()) {
            return 0;
        }
        
        $totalDays = 0;
        foreach ($completedProgresses as $progress) {
            $enrollmentDate = Carbon::parse($progress->enrollment_date);
            $completionDate = Carbon::parse($progress->completion_date);
            $totalDays += $enrollmentDate->diffInDays($completionDate);
        }
        
        return round($totalDays / $completedProgresses->count(), 1);
    }

    private function getTopPerformingCourses(string $tenantId, array $dateRange): array
    {
        return Course::where('tenant_id', $tenantId)
            ->withAvg('studentProgress', 'completion_percentage')
            ->orderBy('student_progress_avg_completion_percentage', 'desc')
            ->limit(10)
            ->get(['id', 'title', 'student_progress_avg_completion_percentage'])
            ->toArray();
    }

    private function getStrugglingStudents(string $tenantId, array $dateRange): array
    {
        return User::where('tenant_id', $tenantId)
            ->whereHas('studentProgress', function ($query) {
                $query->where('completion_percentage', '<', 30);
            })
            ->with(['studentProgress' => function ($query) {
                $query->where('completion_percentage', '<', 30);
            }])
            ->limit(10)
            ->get()
            ->toArray();
    }

    private function getPerformanceDistribution(string $tenantId, array $dateRange): array
    {
        // Get distribution of student performance levels
        $performanceRanges = [
            'excellent' => ['min' => 90, 'max' => 100, 'count' => 0],
            'good' => ['min' => 70, 'max' => 89, 'count' => 0],
            'average' => ['min' => 50, 'max' => 69, 'count' => 0],
            'below_average' => ['min' => 30, 'max' => 49, 'count' => 0],
            'poor' => ['min' => 0, 'max' => 29, 'count' => 0],
        ];
        
        foreach ($performanceRanges as $level => &$range) {
            $range['count'] = StudentProgress::where('tenant_id', $tenantId)
                ->whereBetween('completion_percentage', [$range['min'], $range['max']])
                ->count();
        }
        
        // Calculate percentages
        $totalStudents = StudentProgress::where('tenant_id', $tenantId)->count();
        
        if ($totalStudents > 0) {
            foreach ($performanceRanges as $level => &$range) {
                $range['percentage'] = round(($range['count'] / $totalStudents) * 100, 1);
            }
        }
        
        return $performanceRanges;
    }

    private function getUserTrends(string $tenantId, array $dateRange): array
    {
        // Placeholder for user trends
        return [];
    }

    private function getCourseTrends(string $tenantId, array $dateRange): array
    {
        // Placeholder for course trends
        return [];
    }

    private function getRevenueTrends(string $tenantId, array $dateRange): array
    {
        // Placeholder for revenue trends
        return [];
    }

    private function getUserSegments(string $tenantId, array $dateRange): array
    {
        // Placeholder for user segments
        return [];
    }

    private function getLearningPatterns(string $tenantId, array $dateRange): array
    {
        // Placeholder for learning patterns
        return [];
    }

    private function getDeviceUsage(string $tenantId, array $dateRange): array
    {
        // Placeholder for device usage
        return [];
    }

    private function getPeakLearningHours(string $tenantId, array $dateRange): array
    {
        // Placeholder for peak learning hours
        return [];
    }

    private function getCourseProgression(string $tenantId, array $dateRange): array
    {
        // Placeholder for course progression
        return [];
    }

    private function getCoursePerformanceMetrics(string $tenantId, array $dateRange): array
    {
        // Placeholder for course performance metrics
        return [];
    }

    private function getEnrollmentPatterns(string $tenantId, array $dateRange): array
    {
        // Placeholder for enrollment patterns
        return [];
    }

    private function getCompletionFunnel(string $tenantId, array $dateRange): array
    {
        // Placeholder for completion funnel
        return [];
    }

    private function getContentEffectiveness(string $tenantId, array $dateRange): array
    {
        // Placeholder for content effectiveness
        return [];
    }

    private function getDropOffPoints(string $tenantId, array $dateRange): array
    {
        // Placeholder for drop off points
        return [];
    }

    private function getUserRetention(string $tenantId, array $dateRange): array
    {
        // Placeholder for user retention
        return [];
    }

    private function getCourseRetention(string $tenantId, array $dateRange): array
    {
        // Placeholder for course retention
        return [];
    }

    private function getChurnRate(string $tenantId, array $dateRange): float
    {
        // Placeholder for churn rate
        return 0;
    }

    private function getLifetimeValue(string $tenantId, array $dateRange): float
    {
        // Placeholder for lifetime value
        return 0;
    }
}
