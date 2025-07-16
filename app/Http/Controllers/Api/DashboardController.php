<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\DashboardRequest;
use App\Services\Dashboard\DashboardService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private readonly DashboardService $dashboardService
    ) {}

    /**
     * Get dashboard statistics and metrics
     */
    public function index(DashboardRequest $request): JsonResponse
    {
        try {
            $tenantId = $this->getTenantId();
            
            // Get basic statistics
            $stats = $this->dashboardService->getDashboardStats($tenantId);
            
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

            return $this->successResponse([
                'statistics' => $stats->toArray(),
                'monthly_enrollments' => $monthlyEnrollments,
                'recent_enrollments' => $recentEnrollments,
                'course_performance' => $coursePerformance,
                'student_activity' => $studentActivity,
                'top_courses' => $topCourses
            ], 'Dashboard data retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Error retrieving dashboard data', [
                'error' => $e->getMessage(),
                'tenant_id' => $this->getTenantId(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                message: 'Failed to retrieve dashboard data',
                code: 500
            );
        }
    }

    /**
     * Get dashboard statistics
     */
    public function getStats(DashboardRequest $request): JsonResponse
    {
        try {
            $tenantId = $this->getTenantId();
            $stats = $this->dashboardService->getDashboardStats($tenantId);

            return $this->successResponse(
                data: $stats->toArray(),
                message: 'Dashboard statistics retrieved successfully'
            );

        } catch (\Exception $e) {
            Log::error('Dashboard stats error', [
                'error' => $e->getMessage(),
                'tenant_id' => $this->getTenantId(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                message: 'Failed to retrieve dashboard statistics',
                code: 500
            );
        }
    }

    /**
     * Get recent activity
     */
    public function getActivity(DashboardRequest $request): JsonResponse
    {
        try {
            $tenantId = $this->getTenantId();
            $activities = $this->dashboardService->getRecentActivities($tenantId);

            return $this->successResponse(
                data: $activities->map(fn($activity) => $activity->toArray())->toArray(),
                message: 'Recent activity retrieved successfully'
            );

        } catch (\Exception $e) {
            Log::error('Dashboard activity error', [
                'error' => $e->getMessage(),
                'tenant_id' => $this->getTenantId(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                message: 'Failed to retrieve recent activity',
                code: 500
            );
        }
    }

    /**
     * Get course progress data
     */
    public function getCourses(DashboardRequest $request): JsonResponse
    {
        try {
            $tenantId = $this->getTenantId();
            $courses = $this->dashboardService->getCourseProgress($tenantId);

            return $this->successResponse(
                data: $courses->map(fn($course) => $course->toArray())->toArray(),
                message: 'Course progress retrieved successfully'
            );

        } catch (\Exception $e) {
            Log::error('Dashboard courses error', [
                'error' => $e->getMessage(),
                'tenant_id' => $this->getTenantId(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                message: 'Failed to retrieve course progress',
                code: 500
            );
        }
    }

    /**
     * Get user progress data
     */
    public function getUsers(DashboardRequest $request): JsonResponse
    {
        try {
            $tenantId = $this->getTenantId();
            $users = $this->dashboardService->getUserProgress($tenantId);

            return $this->successResponse(
                data: $users->map(fn($user) => $user->toArray())->toArray(),
                message: 'User progress retrieved successfully'
            );

        } catch (\Exception $e) {
            Log::error('Dashboard users error', [
                'error' => $e->getMessage(),
                'tenant_id' => $this->getTenantId(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                message: 'Failed to retrieve user progress',
                code: 500
            );
        }
    }

    /**
     * Get payment statistics
     */
    public function getPayments(DashboardRequest $request): JsonResponse
    {
        try {
            $tenantId = $this->getTenantId();
            $payments = $this->dashboardService->getPaymentStats($tenantId);

            return $this->successResponse(
                data: $payments->toArray(),
                message: 'Payment statistics retrieved successfully'
            );

        } catch (\Exception $e) {
            Log::error('Dashboard payments error', [
                'error' => $e->getMessage(),
                'tenant_id' => $this->getTenantId(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                message: 'Failed to retrieve payment statistics',
                code: 500
            );
        }
    }

    /**
     * Get current user's tenant ID with proper validation
     */
    private function getTenantId(): string
    {
        $user = Auth::user();
        
        if (!$user) {
            throw new \Exception('User not authenticated');
        }

        if (!$user->tenant_id) {
            throw new \Exception('User does not have a tenant assigned');
        }

        return $user->tenant_id;
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

    /**
     * Get dashboard statistics
     */
    public function getStats(): JsonResponse
    {
        try {
            $user = Auth::user();
            $tenantId = $user->tenant_id;

            // Get basic stats
            $totalUsers = User::where('tenant_id', $tenantId)->count();
            $totalCourses = Course::where('tenant_id', $tenantId)->count();
            $totalEnrollments = DB::table('course_user')
                ->join('courses', 'course_user.course_id', '=', 'courses.id')
                ->where('courses.tenant_id', $tenantId)
                ->where('course_user.role', 'student')
                ->count();
            $totalRevenue = CoursePurchase::where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->sum('amount_paid');

            // Calculate growth rates
            $lastMonth = Carbon::now()->subMonth();
            $usersLastMonth = User::where('tenant_id', $tenantId)
                ->where('created_at', '>=', $lastMonth)
                ->count();
            $userGrowthRate = $totalUsers > 0 ? ($usersLastMonth / $totalUsers) * 100 : 0;

            // Calculate completion rate
            $completedCourses = StudentProgress::where('tenant_id', $tenantId)
                ->where('completion_percentage', 100)
                ->count();
            $courseCompletionRate = $totalEnrollments > 0 ? ($completedCourses / $totalEnrollments) * 100 : 0;

            // Active users (users with activity in last 30 days)
            $activeUsers = User::where('tenant_id', $tenantId)
                ->where('updated_at', '>=', Carbon::now()->subDays(30))
                ->count();

            // Pending enrollments
            $pendingEnrollments = StudentProgress::where('tenant_id', $tenantId)
                ->where('completion_percentage', 0)
                ->count();

            $stats = [
                'totalUsers' => $totalUsers,
                'totalCourses' => $totalCourses,
                'totalEnrollments' => $totalEnrollments,
                'totalRevenue' => $totalRevenue,
                'userGrowthRate' => round($userGrowthRate, 1),
                'courseCompletionRate' => round($courseCompletionRate, 1),
                'activeUsers' => $activeUsers,
                'pendingEnrollments' => $pendingEnrollments,
            ];

            return $this->successResponse($stats, 'Dashboard statistics retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Dashboard stats error: ' . $e->getMessage());
            return $this->errorResponse('Failed to retrieve dashboard statistics', 500);
        }
    }

    /**
     * Get recent activity
     */
    public function getActivity(): JsonResponse
    {
        try {
            $user = Auth::user();
            $tenantId = $user->tenant_id;

            $activities = [];

            // Recent enrollments
            $recentEnrollments = DB::table('course_user')
                ->join('courses', 'course_user.course_id', '=', 'courses.id')
                ->join('users', 'course_user.user_id', '=', 'users.id')
                ->where('courses.tenant_id', $tenantId)
                ->where('course_user.role', 'student')
                ->where('course_user.created_at', '>=', Carbon::now()->subDays(7))
                ->orderBy('course_user.created_at', 'desc')
                ->limit(10)
                ->get();

            foreach ($recentEnrollments as $enrollment) {
                $activities[] = [
                    'id' => $enrollment->id,
                    'type' => 'enrollment',
                    'message' => $enrollment->name . ' enrolled in ' . $enrollment->title,
                    'timestamp' => Carbon::parse($enrollment->created_at)->diffForHumans(),
                    'user' => [
                        'name' => $enrollment->name,
                        'email' => $enrollment->email,
                        'avatar' => '/avatars/default.png',
                    ],
                    'metadata' => [
                        'course_id' => $enrollment->course_id,
                        'course_title' => $enrollment->title,
                    ],
                ];
            }

            // Recent completions
            $recentCompletions = StudentProgress::with(['user', 'course'])
                ->where('tenant_id', $tenantId)
                ->where('completion_percentage', 100)
                ->where('completed_at', '>=', Carbon::now()->subDays(7))
                ->orderBy('completed_at', 'desc')
                ->limit(10)
                ->get();

            foreach ($recentCompletions as $completion) {
                $activities[] = [
                    'id' => 'completion_' . $completion->id,
                    'type' => 'completion',
                    'message' => $completion->user->name . ' completed ' . $completion->course->title,
                    'timestamp' => $completion->completed_at->diffForHumans(),
                    'user' => [
                        'name' => $completion->user->name,
                        'email' => $completion->user->email,
                        'avatar' => '/avatars/default.png',
                    ],
                    'metadata' => [
                        'course_id' => $completion->course_id,
                        'course_title' => $completion->course->title,
                    ],
                ];
            }

            // Recent payments
            $recentPayments = CoursePurchase::with(['student', 'course'])
                ->where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->where('created_at', '>=', Carbon::now()->subDays(7))
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();

            foreach ($recentPayments as $payment) {
                $activities[] = [
                    'id' => 'payment_' . $payment->id,
                    'type' => 'payment',
                    'message' => $payment->student->name . ' made a payment of ' . $payment->currency . ' ' . number_format($payment->amount_paid, 2),
                    'timestamp' => $payment->created_at->diffForHumans(),
                    'user' => [
                        'name' => $payment->student->name,
                        'email' => $payment->student->email,
                        'avatar' => '/avatars/default.png',
                    ],
                    'metadata' => [
                        'course_id' => $payment->course_id,
                        'course_title' => $payment->course->title,
                        'amount' => $payment->amount_paid,
                    ],
                ];
            }

            // Sort all activities by timestamp
            usort($activities, function ($a, $b) {
                return strtotime($b['timestamp']) - strtotime($a['timestamp']);
            });

            // Take only the latest 15 activities
            $activities = array_slice($activities, 0, 15);

            return $this->successResponse($activities, 'Recent activity retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Dashboard activity error: ' . $e->getMessage());
            return $this->errorResponse('Failed to retrieve recent activity', 500);
        }
    }

    /**
     * Get course progress data
     */
    public function getCourses(): JsonResponse
    {
        try {
            $user = Auth::user();
            $tenantId = $user->tenant_id;

            $courses = Course::where('tenant_id', $tenantId)
                ->with(['instructor'])
                ->withCount([
                    'users as enrollments_count' => function ($query) {
                        $query->wherePivot('role', 'student');
                    }
                ])
                ->get()
                ->map(function ($course) {
                    // Get completions count
                    $completions = StudentProgress::where('course_id', $course->id)
                        ->where('completion_percentage', 100)
                        ->count();

                    $completionRate = $course->enrollments_count > 0 ?
                        ($completions / $course->enrollments_count) * 100 : 0;

                    // Calculate average progress
                    $averageProgress = StudentProgress::where('course_id', $course->id)
                        ->avg('completion_percentage') ?? 0;

                    return [
                        'id' => $course->id,
                        'title' => $course->title,
                        'enrollments' => $course->enrollments_count,
                        'completions' => $completions,
                        'completionRate' => round($completionRate, 1),
                        'averageProgress' => round($averageProgress, 1),
                        'instructor' => $course->instructor->name ?? 'Unknown',
                        'status' => $course->is_active ? 'active' : 'inactive',
                    ];
                });

            return $this->successResponse($courses, 'Course progress retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Dashboard courses error: ' . $e->getMessage());
            return $this->errorResponse('Failed to retrieve course progress', 500);
        }
    }

    /**
     * Get user progress data
     */
    public function getUsers(): JsonResponse
    {
        try {
            $user = Auth::user();
            $tenantId = $user->tenant_id;

            $users = User::where('tenant_id', $tenantId)
                ->where('role', '!=', 'super_admin')
                ->get()
                ->map(function ($user) {
                    // Get enrollment count
                    $enrollments = DB::table('course_user')
                        ->join('courses', 'course_user.course_id', '=', 'courses.id')
                        ->where('courses.tenant_id', $user->tenant_id)
                        ->where('course_user.user_id', $user->id)
                        ->where('course_user.role', 'student')
                        ->count();

                    // Get completed courses count
                    $completedCourses = StudentProgress::where('user_id', $user->id)
                        ->where('completion_percentage', 100)
                        ->count();

                    // Calculate total progress
                    $totalProgress = StudentProgress::where('user_id', $user->id)
                        ->avg('completion_percentage') ?? 0;

                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'avatar' => '/avatars/default.png',
                        'enrolledCourses' => $enrollments,
                        'completedCourses' => $completedCourses,
                        'totalProgress' => round($totalProgress, 1),
                        'lastActivity' => $user->updated_at->diffForHumans(),
                        'role' => $user->role,
                    ];
                });

            return $this->successResponse($users, 'User progress retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Dashboard users error: ' . $e->getMessage());
            return $this->errorResponse('Failed to retrieve user progress', 500);
        }
    }

    /**
     * Get payment statistics
     */
    public function getPayments(): JsonResponse
    {
        try {
            $user = Auth::user();
            $tenantId = $user->tenant_id;

            $totalRevenue = CoursePurchase::where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->sum('amount_paid');

            $monthlyRevenue = CoursePurchase::where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->where('created_at', '>=', Carbon::now()->startOfMonth())
                ->sum('amount_paid');

            $pendingPayments = CoursePurchase::where('tenant_id', $tenantId)
                ->where('is_active', false)
                ->count();

            $successfulPayments = CoursePurchase::where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->count();

            $averageOrderValue = CoursePurchase::where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->avg('amount_paid') ?? 0;

            // Calculate revenue growth
            $lastMonthRevenue = CoursePurchase::where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->where('created_at', '>=', Carbon::now()->subMonth()->startOfMonth())
                ->where('created_at', '<=', Carbon::now()->subMonth()->endOfMonth())
                ->sum('amount_paid');

            $revenueGrowth = $lastMonthRevenue > 0 ?
                (($monthlyRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100 : 0;

            $stats = [
                'totalRevenue' => $totalRevenue,
                'monthlyRevenue' => $monthlyRevenue,
                'pendingPayments' => $pendingPayments,
                'successfulPayments' => $successfulPayments,
                'failedPayments' => 0, // Not tracked in current schema
                'averageOrderValue' => round($averageOrderValue, 2),
                'revenueGrowth' => round($revenueGrowth, 1),
            ];

            return $this->successResponse($stats, 'Payment statistics retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Dashboard payments error: ' . $e->getMessage());
            return $this->errorResponse('Failed to retrieve payment statistics', 500);
        }
    }
}
