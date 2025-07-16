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

            // Get recent activities
            $activities = $this->dashboardService->getRecentActivities($tenantId);

            // Get course progress
            $courses = $this->dashboardService->getCourseProgress($tenantId);

            // Get user progress
            $users = $this->dashboardService->getUserProgress($tenantId);

            // Get payment stats
            $payments = $this->dashboardService->getPaymentStats($tenantId);

            return $this->successResponse([
                'statistics' => $stats->toArray(),
                'recent_activities' => $activities->map(fn($activity) => $activity->toArray())->toArray(),
                'course_progress' => $courses->map(fn($course) => $course->toArray())->toArray(),
                'user_progress' => $users->map(fn($user) => $user->toArray())->toArray(),
                'payment_stats' => $payments->toArray(),
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
    public function getChartData(DashboardRequest $request): JsonResponse
    {
        try {
            $tenantId = $this->getTenantId();

            // For now, return a simple response. This can be expanded with a dedicated service method
            return $this->successResponse(
                data: [],
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
}
