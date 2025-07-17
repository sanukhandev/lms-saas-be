<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Analytics\AnalyticsService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AnalyticsController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private readonly AnalyticsService $analyticsService
    ) {}

    /**
     * Get analytics overview with key metrics
     */
    public function overview(Request $request): JsonResponse
    {
        try {
            $tenantId = $this->getTenantId();
            $timeRange = $request->input('time_range', '30d'); // 7d, 30d, 90d, 1y

            $overview = $this->analyticsService->getAnalyticsOverview($tenantId, $timeRange);

            return $this->successResponse(
                data: $overview,
                message: 'Analytics overview retrieved successfully'
            );
        } catch (\Exception $e) {
            Log::error('Analytics overview error', [
                'error' => $e->getMessage(),
                'tenant_id' => $this->getTenantId(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                message: 'Failed to retrieve analytics overview',
                code: 500
            );
        }
    }

    /**
     * Get engagement metrics
     */
    public function getEngagementMetrics(Request $request): JsonResponse
    {
        try {
            $tenantId = $this->getTenantId();
            $timeRange = $request->input('time_range', '30d');

            $metrics = $this->analyticsService->getEngagementMetrics($tenantId, $timeRange);

            return $this->successResponse(
                data: $metrics,
                message: 'Engagement metrics retrieved successfully'
            );
        } catch (\Exception $e) {
            Log::error('Engagement metrics error', [
                'error' => $e->getMessage(),
                'tenant_id' => $this->getTenantId(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                message: 'Failed to retrieve engagement metrics',
                code: 500
            );
        }
    }

    /**
     * Get performance metrics
     */
    public function getPerformanceMetrics(Request $request): JsonResponse
    {
        try {
            $tenantId = $this->getTenantId();
            $timeRange = $request->input('time_range', '30d');

            $metrics = $this->analyticsService->getPerformanceMetrics($tenantId, $timeRange);

            return $this->successResponse(
                data: $metrics,
                message: 'Performance metrics retrieved successfully'
            );
        } catch (\Exception $e) {
            Log::error('Performance metrics error', [
                'error' => $e->getMessage(),
                'tenant_id' => $this->getTenantId(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                message: 'Failed to retrieve performance metrics',
                code: 500
            );
        }
    }

    /**
     * Get trend analysis
     */
    public function getTrendAnalysis(Request $request): JsonResponse
    {
        try {
            $tenantId = $this->getTenantId();
            $timeRange = $request->input('time_range', '30d');
            $metric = $request->input('metric', 'all'); // users, courses, revenue, engagement

            $trends = $this->analyticsService->getTrendAnalysis($tenantId, $timeRange, $metric);

            return $this->successResponse(
                data: $trends,
                message: 'Trend analysis retrieved successfully'
            );
        } catch (\Exception $e) {
            Log::error('Trend analysis error', [
                'error' => $e->getMessage(),
                'tenant_id' => $this->getTenantId(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                message: 'Failed to retrieve trend analysis',
                code: 500
            );
        }
    }

    /**
     * Get user behavior analytics
     */
    public function getUserBehaviorAnalytics(Request $request): JsonResponse
    {
        try {
            $tenantId = $this->getTenantId();
            $timeRange = $request->input('time_range', '30d');

            $analytics = $this->analyticsService->getUserBehaviorAnalytics($tenantId, $timeRange);

            return $this->successResponse(
                data: $analytics,
                message: 'User behavior analytics retrieved successfully'
            );
        } catch (\Exception $e) {
            Log::error('User behavior analytics error', [
                'error' => $e->getMessage(),
                'tenant_id' => $this->getTenantId(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                message: 'Failed to retrieve user behavior analytics',
                code: 500
            );
        }
    }

    /**
     * Get course analytics
     */
    public function getCourseAnalytics(Request $request): JsonResponse
    {
        try {
            $tenantId = $this->getTenantId();
            $timeRange = $request->input('time_range', '30d');

            $analytics = $this->analyticsService->getCourseAnalytics($tenantId, $timeRange);

            return $this->successResponse(
                data: $analytics,
                message: 'Course analytics retrieved successfully'
            );
        } catch (\Exception $e) {
            Log::error('Course analytics error', [
                'error' => $e->getMessage(),
                'tenant_id' => $this->getTenantId(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                message: 'Failed to retrieve course analytics',
                code: 500
            );
        }
    }

    /**
     * Get revenue analytics
     */
    public function getRevenueAnalytics(Request $request): JsonResponse
    {
        try {
            $tenantId = $this->getTenantId();
            $timeRange = $request->input('time_range', '30d');

            $analytics = $this->analyticsService->getRevenueAnalytics($tenantId, $timeRange);

            return $this->successResponse(
                data: $analytics,
                message: 'Revenue analytics retrieved successfully'
            );
        } catch (\Exception $e) {
            Log::error('Revenue analytics error', [
                'error' => $e->getMessage(),
                'tenant_id' => $this->getTenantId(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                message: 'Failed to retrieve revenue analytics',
                code: 500
            );
        }
    }

    /**
     * Get retention metrics
     */
    public function getRetentionMetrics(Request $request): JsonResponse
    {
        try {
            $tenantId = $this->getTenantId();
            $timeRange = $request->input('time_range', '30d');

            $metrics = $this->analyticsService->getRetentionMetrics($tenantId, $timeRange);

            return $this->successResponse(
                data: $metrics,
                message: 'Retention metrics retrieved successfully'
            );
        } catch (\Exception $e) {
            Log::error('Retention metrics error', [
                'error' => $e->getMessage(),
                'tenant_id' => $this->getTenantId(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                message: 'Failed to retrieve retention metrics',
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
}
