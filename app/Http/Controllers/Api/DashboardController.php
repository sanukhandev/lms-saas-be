<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\User;
use App\Models\Category;
use App\Models\StudentProgress;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Get dashboard statistics and metrics
     */
    public function index(): JsonResponse
    {
        try {
            $tenantId = Auth::user()->tenant_id;

            // Get basic statistics
            $stats = [
                'total_students' => User::where('tenant_id', $tenantId)->where('role', 'student')->count(),
                'total_courses' => Course::where('tenant_id', $tenantId)->count(),
                'active_courses' => Course::where('tenant_id', $tenantId)->where('is_active', true)->count(),
                'total_enrollments' => DB::table('course_user')
                    ->join('courses', 'course_user.course_id', '=', 'courses.id')
                    ->where('courses.tenant_id', $tenantId)
                    ->where('course_user.role', 'student')
                    ->count(),
                'total_categories' => Category::where('tenant_id', $tenantId)->count(),
                'completion_rate' => $this->calculateAverageCompletionRate($tenantId)
            ];

            // Get monthly enrollment data for the last 12 months
            $monthlyEnrollments = $this->getMonthlyEnrollments($tenantId);

            // Get recent enrollments
            $recentEnrollments = $this->getRecentEnrollments($tenantId);

            // Get course performance data
            $coursePerformance = $this->getCoursePerformance($tenantId);

            // Get student activity feed
            $studentActivity = $this->getStudentActivity($tenantId);

            // Get top performing courses
            $topCourses = $this->getTopPerformingCourses($tenantId);

            return response()->json([
                'success' => true,
                'data' => [
                    'statistics' => $stats,
                    'monthly_enrollments' => $monthlyEnrollments,
                    'recent_enrollments' => $recentEnrollments,
                    'course_performance' => $coursePerformance,
                    'student_activity' => $studentActivity,
                    'top_courses' => $topCourses
                ],
                'message' => 'Dashboard data retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error retrieving dashboard data: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving dashboard data'
            ], 500);
        }
    }

    /**
     * Get enrollment and completion data for charts
     */
    public function getChartData(): JsonResponse
    {
        try {
            $tenantId = Auth::user()->tenant_id;

            // Get monthly data for the last 12 months
            $monthlyData = collect();
            for ($i = 11; $i >= 0; $i--) {
                $date = Carbon::now()->subMonths($i);
                $monthStart = $date->copy()->startOfMonth();
                $monthEnd = $date->copy()->endOfMonth();

                $enrollments = DB::table('course_user')
                    ->join('courses', 'course_user.course_id', '=', 'courses.id')
                    ->where('courses.tenant_id', $tenantId)
                    ->where('course_user.role', 'student')
                    ->whereBetween('course_user.created_at', [$monthStart, $monthEnd])
                    ->count();

                $completions = StudentProgress::where('tenant_id', $tenantId)
                    ->where('completion_percentage', 100)
                    ->whereBetween('completed_at', [$monthStart, $monthEnd])
                    ->count();

                $monthlyData->push([
                    'month' => $date->format('M'),
                    'enrollments' => $enrollments,
                    'completions' => $completions
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => $monthlyData,
                'message' => 'Chart data retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error retrieving chart data: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving chart data'
            ], 500);
        }
    }

    /**
     * Calculate average completion rate across all courses
     */
    private function calculateAverageCompletionRate(string $tenantId): float
    {
        $avgCompletion = StudentProgress::where('tenant_id', $tenantId)
            ->avg('completion_percentage');

        return round($avgCompletion ?? 0, 2);
    }

    /**
     * Get monthly enrollment data
     */
    private function getMonthlyEnrollments(string $tenantId): array
    {
        $enrollments = DB::table('course_user')
            ->join('courses', 'course_user.course_id', '=', 'courses.id')
            ->where('courses.tenant_id', $tenantId)
            ->where('course_user.role', 'student')
            ->where('course_user.created_at', '>=', Carbon::now()->subMonths(12))
            ->select(
                DB::raw('YEAR(course_user.created_at) as year'),
                DB::raw('MONTH(course_user.created_at) as month'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('year', 'month')
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get();

        return $enrollments->toArray();
    }

    /**
     * Get recent enrollments with student and course details
     */
    private function getRecentEnrollments(string $tenantId): array
    {
        $enrollments = DB::table('course_user')
            ->join('courses', 'course_user.course_id', '=', 'courses.id')
            ->join('users', 'course_user.user_id', '=', 'users.id')
            ->leftJoin('student_progress', function ($join) {
                $join->on('course_user.course_id', '=', 'student_progress.course_id')
                     ->on('course_user.user_id', '=', 'student_progress.user_id');
            })
            ->where('courses.tenant_id', $tenantId)
            ->where('course_user.role', 'student')
            ->select(
                'users.name as student_name',
                'users.email as student_email',
                'courses.title as course_title',
                'course_user.created_at as enrolled_at',
                'student_progress.completion_percentage'
            )
            ->orderBy('course_user.created_at', 'desc')
            ->limit(10)
            ->get();

        return $enrollments->toArray();
    }

    /**
     * Get course performance data
     */
    private function getCoursePerformance(string $tenantId): array
    {
        $courses = Course::where('tenant_id', $tenantId)
            ->with(['users' => function ($query) {
                $query->wherePivot('role', 'student');
            }])
            ->get();

        $performance = $courses->map(function ($course) {
            $totalStudents = $course->users->count();
            $avgCompletion = StudentProgress::where('course_id', $course->id)
                ->avg('completion_percentage');

            return [
                'course_id' => $course->id,
                'course_title' => $course->title,
                'total_students' => $totalStudents,
                'avg_completion' => round($avgCompletion ?? 0, 2),
                'completion_rate' => round(($avgCompletion ?? 0) / 100 * 5, 1), // Convert to 5-star rating
                'status' => $course->is_active ? 'Active' : 'Inactive'
            ];
        });

        return $performance->toArray();
    }

    /**
     * Get student activity feed
     */
    private function getStudentActivity(string $tenantId): array
    {
        $activities = collect();

        // Recent enrollments
        $recentEnrollments = DB::table('course_user')
            ->join('courses', 'course_user.course_id', '=', 'courses.id')
            ->join('users', 'course_user.user_id', '=', 'users.id')
            ->where('courses.tenant_id', $tenantId)
            ->where('course_user.role', 'student')
            ->where('course_user.created_at', '>=', Carbon::now()->subDays(7))
            ->select(
                'users.name as student_name',
                'courses.title as course_title',
                'course_user.created_at as activity_time',
                DB::raw("'enrollment' as activity_type")
            )
            ->get();

        // Recent completions
        $recentCompletions = StudentProgress::where('tenant_id', $tenantId)
            ->where('completion_percentage', 100)
            ->where('completed_at', '>=', Carbon::now()->subDays(7))
            ->with(['user', 'course'])
            ->get()
            ->map(function ($progress) {
                return [
                    'student_name' => $progress->user->name,
                    'course_title' => $progress->course->title,
                    'activity_time' => $progress->completed_at,
                    'activity_type' => 'completion'
                ];
            });

        // Recent progress updates
        $recentProgress = StudentProgress::where('tenant_id', $tenantId)
            ->where('last_accessed', '>=', Carbon::now()->subDays(7))
            ->where('completion_percentage', '<', 100)
            ->where('completion_percentage', '>', 0)
            ->with(['user', 'course'])
            ->orderBy('last_accessed', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($progress) {
                return [
                    'student_name' => $progress->user->name,
                    'course_title' => $progress->course->title,
                    'activity_time' => $progress->last_accessed,
                    'activity_type' => 'progress',
                    'completion_percentage' => $progress->completion_percentage
                ];
            });

        $activities = $activities->merge($recentEnrollments)
            ->merge($recentCompletions)
            ->merge($recentProgress)
            ->sortByDesc('activity_time')
            ->take(15)
            ->values();

        return $activities->toArray();
    }

    /**
     * Get top performing courses
     */
    private function getTopPerformingCourses(string $tenantId): array
    {
        $courses = Course::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->with(['users' => function ($query) {
                $query->wherePivot('role', 'student');
            }])
            ->get()
            ->map(function ($course) {
                $totalStudents = $course->users->count();
                $avgCompletion = StudentProgress::where('course_id', $course->id)
                    ->avg('completion_percentage');

                return [
                    'course_id' => $course->id,
                    'course_title' => $course->title,
                    'total_students' => $totalStudents,
                    'avg_completion' => round($avgCompletion ?? 0, 2),
                    'level' => $course->schedule_level,
                    'performance_score' => ($totalStudents * 0.3) + (($avgCompletion ?? 0) * 0.7)
                ];
            })
            ->sortByDesc('performance_score')
            ->take(10)
            ->values();

        return $courses->toArray();
    }
}
