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

            // Get recent activities (limited to 10 for dashboard overview)
            $activities = $this->dashboardService->getRecentActivities($tenantId, 1, 10);

            // Get course progress (limited to 5 for dashboard overview)
            $courses = $this->dashboardService->getCourseProgress($tenantId, 1, 5);

            // Get user progress (limited to 5 for dashboard overview)
            $users = $this->dashboardService->getUserProgress($tenantId, 1, 5);

            // Get payment stats
            $payments = $this->dashboardService->getPaymentStats($tenantId);

            // Get chart data
            $chartData = $this->dashboardService->getChartData($tenantId);

            return $this->successResponse([
                'overview' => [
                    'statistics' => $stats->toArray(),
                    'charts' => $chartData->toArray(),
                    'quick_stats' => [
                        'completion_rate' => $stats->courseCompletionRate,
                        'active_users' => $stats->activeUsers,
                        'pending_enrollments' => $stats->pendingEnrollments,
                        'revenue_growth' => $payments->revenueGrowth,
                    ],
                ],
                'recent_activities' => $activities->getCollection()->map(fn($activity) => $activity->toArray())->toArray(),
                'course_progress' => $courses->getCollection()->map(fn($course) => $course->toArray())->toArray(),
                'user_progress' => $users->getCollection()->map(fn($user) => $user->toArray())->toArray(),
                'payment_stats' => $payments->toArray(),
                'meta' => [
                    'activities_pagination' => [
                        'current_page' => $activities->currentPage(),
                        'per_page' => $activities->perPage(),
                        'total' => $activities->total(),
                        'has_more' => $activities->hasMorePages(),
                    ],
                    'courses_pagination' => [
                        'current_page' => $courses->currentPage(),
                        'per_page' => $courses->perPage(),
                        'total' => $courses->total(),
                        'has_more' => $courses->hasMorePages(),
                    ],
                    'users_pagination' => [
                        'current_page' => $users->currentPage(),
                        'per_page' => $users->perPage(),
                        'total' => $users->total(),
                        'has_more' => $users->hasMorePages(),
                    ],
                ],
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
     * Get recent activity with pagination
     */
    public function getActivity(DashboardRequest $request): JsonResponse
    {
        try {
            $tenantId = $this->getTenantId();
            $page = $request->input('page', 1);
            $perPage = $request->input('per_page', 15);

            $activities = $this->dashboardService->getRecentActivities($tenantId, $page, $perPage);

            return $this->successPaginated(
                $activities,
                'Recent activity retrieved successfully'
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
     * Get course progress data with pagination
     */
    public function getCourses(DashboardRequest $request): JsonResponse
    {
        try {
            $tenantId = $this->getTenantId();
            $page = $request->input('page', 1);
            $perPage = $request->input('per_page', 15);

            $courses = $this->dashboardService->getCourseProgress($tenantId, $page, $perPage);

            return $this->successPaginated(
                $courses,
                'Course progress retrieved successfully'
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
     * Get user progress data with pagination
     */
    public function getUsers(DashboardRequest $request): JsonResponse
    {
        try {
            $tenantId = $this->getTenantId();
            $page = $request->input('page', 1);
            $perPage = $request->input('per_page', 15);
            $search = $request->input('search');
            $role = $request->input('role');
            $status = $request->input('status');

            $users = $this->dashboardService->getUsersForManagement($tenantId, $page, $perPage, $search, $role, $status);

            return $this->successPaginated(
                $users,
                'Users retrieved successfully'
            );
        } catch (\Exception $e) {
            Log::error('Dashboard users error', [
                'error' => $e->getMessage(),
                'tenant_id' => $this->getTenantId(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                message: 'Failed to retrieve users',
                code: 500
            );
        }
    }

    /**
     * Get user statistics for dashboard
     */
    public function getUserStats(DashboardRequest $request): JsonResponse
    {
        try {
            $tenantId = $this->getTenantId();
            $stats = $this->dashboardService->getUserStats($tenantId);

            return $this->successResponse(
                data: (array) $stats,
                message: 'User statistics retrieved successfully'
            );
        } catch (\Exception $e) {
            Log::error('Dashboard user stats error', [
                'error' => $e->getMessage(),
                'tenant_id' => $this->getTenantId(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                message: 'Failed to retrieve user statistics',
                code: 500
            );
        }
    }

    /**
     * Get user activity feed
     */
    public function getUserActivity(DashboardRequest $request): JsonResponse
    {
        try {
            $tenantId = $this->getTenantId();
            $page = $request->input('page', 1);
            $perPage = $request->input('per_page', 10);
            $userId = $request->input('user_id');

            $activities = $this->dashboardService->getUserActivityFeed($tenantId, $page, $perPage, $userId);

            return $this->successPaginated(
                $activities,
                'User activity retrieved successfully'
            );
        } catch (\Exception $e) {
            Log::error('Dashboard user activity error', [
                'error' => $e->getMessage(),
                'tenant_id' => $this->getTenantId(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                message: 'Failed to retrieve user activity',
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
    public function getChartData(DashboardRequest $request): JsonResponse
    {
        try {
            $tenantId = $this->getTenantId();
            $chartData = $this->dashboardService->getChartData($tenantId);

            return $this->successResponse(
                data: $chartData->toArray(),
                message: 'Chart data retrieved successfully'
            );
        } catch (\Exception $e) {
            Log::error('Error retrieving chart data', [
                'error' => $e->getMessage(),
                'tenant_id' => $this->getTenantId(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                message: 'Failed to retrieve chart data',
                code: 500
            );
        }
    }

    /**
     * Get dashboard overview with optimized structure for frontend
     */
    public function overview(DashboardRequest $request): JsonResponse
    {
        try {
            $tenantId = $this->getTenantId();

            // Get all data concurrently
            $stats = $this->dashboardService->getDashboardStats($tenantId);
            $chartData = $this->dashboardService->getChartData($tenantId);
            $activities = $this->dashboardService->getRecentActivities($tenantId, 1, 8); // Only 8 for overview
            $payments = $this->dashboardService->getPaymentStats($tenantId);

            return $this->successResponse([
                'cards' => [
                    'main_stats' => [
                        [
                            'title' => 'Total Users',
                            'value' => $stats->totalUsers,
                            'description' => 'Active learners',
                            'icon' => 'users',
                            'trend' => [
                                'value' => $stats->userGrowthRate,
                                'isPositive' => $stats->userGrowthRate >= 0,
                            ],
                        ],
                        [
                            'title' => 'Total Courses',
                            'value' => $stats->totalCourses,
                            'description' => 'Available courses',
                            'icon' => 'book',
                        ],
                        [
                            'title' => 'Total Enrollments',
                            'value' => $stats->totalEnrollments,
                            'description' => 'Course enrollments',
                            'icon' => 'clipboard-list',
                        ],
                        [
                            'title' => 'Revenue',
                            'value' => '$' . number_format($stats->totalRevenue),
                            'description' => 'Total revenue',
                            'icon' => 'currency-dollar',
                        ],
                    ],
                    'quick_stats' => [
                        'completion_rate' => $stats->courseCompletionRate,
                        'active_users' => $stats->activeUsers,
                        'pending_enrollments' => $stats->pendingEnrollments,
                        'revenue_growth' => $payments->revenueGrowth,
                    ],
                ],
                'charts' => [
                    'enrollment_trends' => $chartData->enrollmentTrends,
                    'completion_trends' => $chartData->completionTrends,
                    'revenue_trends' => $chartData->revenueTrends,
                    'category_distribution' => $chartData->categoryDistribution,
                    'user_activity_trends' => $chartData->userActivityTrends,
                    'monthly_stats' => $chartData->monthlyStats,
                ],
                'recent_activities' => $activities->getCollection()->map(fn($activity) => $activity->toArray())->toArray(),
                'layout' => [
                    'grid' => [
                        'main_stats' => ['span' => 'full', 'cols' => 4],
                        'charts' => ['span' => 4, 'priority' => 1],
                        'recent_activities' => ['span' => 4, 'priority' => 2],
                        'quick_stats' => ['span' => 3, 'priority' => 3],
                    ],
                ],
            ], 'Dashboard overview retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Error retrieving dashboard overview', [
                'error' => $e->getMessage(),
                'tenant_id' => $this->getTenantId(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                message: 'Failed to retrieve dashboard overview',
                code: 500
            );
        }
    }
}
