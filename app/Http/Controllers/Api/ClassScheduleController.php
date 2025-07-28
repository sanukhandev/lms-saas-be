<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ClassSchedule\{
    ScheduleClassRequest,
    UpdateScheduleRequest,
    CreateTeachingPlanRequest,
    UpdateTeachingPlanRequest,
    BulkScheduleRequest
};
use App\Services\ClassSchedule\ClassScheduleService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ClassScheduleController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private readonly ClassScheduleService $classScheduleService
    ) {}

    /**
     * Get all classes for a course
     */
    public function getCourseClasses(string $courseId): JsonResponse
    {
        try {
            $tenantId = $this->getTenantId();
            $classes = $this->classScheduleService->getCourseClasses($courseId, $tenantId);

            return $this->successResponse(
                $classes->toArray(),
                'Course classes retrieved successfully'
            );
        } catch (\Exception $e) {
            Log::error('Error retrieving course classes', [
                'error' => $e->getMessage(),
                'course_id' => $courseId,
                'tenant_id' => $this->getTenantId(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                message: 'Failed to retrieve course classes',
                code: 500
            );
        }
    }

    /**
     * Get classes for specific content
     */
    public function getContentClasses(string $courseId, string $contentId): JsonResponse
    {
        try {
            $tenantId = $this->getTenantId();
            $classes = $this->classScheduleService->getContentClasses($courseId, $contentId, $tenantId);

            return $this->successResponse(
                $classes->toArray(),
                'Content classes retrieved successfully'
            );
        } catch (\Exception $e) {
            Log::error('Error retrieving content classes', [
                'error' => $e->getMessage(),
                'course_id' => $courseId,
                'content_id' => $contentId,
                'tenant_id' => $this->getTenantId(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                message: 'Failed to retrieve content classes',
                code: 500
            );
        }
    }

    /**
     * Schedule a new class
     */
    public function scheduleClass(ScheduleClassRequest $request, string $courseId): JsonResponse
    {
        try {
            $tenantId = $this->getTenantId();
            $data = $request->validated();
            $data['course_id'] = $courseId;

            $session = $this->classScheduleService->scheduleClass($data, $tenantId);

            return $this->successResponse(
                $session->toArray(),
                'Class scheduled successfully',
                201
            );
        } catch (\Exception $e) {
            Log::error('Error scheduling class', [
                'error' => $e->getMessage(),
                'course_id' => $courseId,
                'tenant_id' => $this->getTenantId(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                message: 'Failed to schedule class',
                code: 500
            );
        }
    }

    /**
     * Update scheduled class
     */
    public function updateSchedule(UpdateScheduleRequest $request, string $courseId, string $sessionId): JsonResponse
    {
        try {
            $tenantId = $this->getTenantId();
            $data = $request->validated();

            $session = $this->classScheduleService->updateSchedule($sessionId, $data, $tenantId);

            if (!$session) {
                return $this->errorResponse(
                    message: 'Class session not found',
                    code: 404
                );
            }

            return $this->successResponse(
                $session->toArray(),
                'Class schedule updated successfully'
            );
        } catch (\Exception $e) {
            Log::error('Error updating class schedule', [
                'error' => $e->getMessage(),
                'course_id' => $courseId,
                'session_id' => $sessionId,
                'tenant_id' => $this->getTenantId(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                message: 'Failed to update class schedule',
                code: 500
            );
        }
    }

    /**
     * Cancel a scheduled class
     */
    public function cancelClass(string $courseId, string $sessionId): JsonResponse
    {
        try {
            $tenantId = $this->getTenantId();
            $result = $this->classScheduleService->cancelClass($sessionId, $tenantId);

            if (!$result) {
                return $this->errorResponse(
                    message: 'Class session not found',
                    code: 404
                );
            }

            return $this->successResponse(
                ['cancelled' => true],
                'Class cancelled successfully'
            );
        } catch (\Exception $e) {
            Log::error('Error cancelling class', [
                'error' => $e->getMessage(),
                'course_id' => $courseId,
                'session_id' => $sessionId,
                'tenant_id' => $this->getTenantId(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                message: 'Failed to cancel class',
                code: 500
            );
        }
    }

    /**
     * Get class planner for course
     */
    public function getClassPlanner(string $courseId): JsonResponse
    {
        try {
            $tenantId = $this->getTenantId();
            $planner = $this->classScheduleService->getClassPlanner($courseId, $tenantId);

            return $this->successResponse(
                $planner->toArray(),
                'Class planner retrieved successfully'
            );
        } catch (\Exception $e) {
            Log::error('Error retrieving class planner', [
                'error' => $e->getMessage(),
                'course_id' => $courseId,
                'tenant_id' => $this->getTenantId(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                message: 'Failed to retrieve class planner',
                code: 500
            );
        }
    }

    /**
     * Create teaching plan
     */
    public function createTeachingPlan(CreateTeachingPlanRequest $request, string $courseId): JsonResponse
    {
        try {
            $tenantId = $this->getTenantId();
            $data = $request->validated();
            $data['course_id'] = $courseId;

            $plan = $this->classScheduleService->createTeachingPlan($data, $tenantId);

            return $this->successResponse(
                $plan->toArray(),
                'Teaching plan created successfully',
                201
            );
        } catch (\Exception $e) {
            Log::error('Error creating teaching plan', [
                'error' => $e->getMessage(),
                'course_id' => $courseId,
                'tenant_id' => $this->getTenantId(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                message: 'Failed to create teaching plan',
                code: 500
            );
        }
    }

    /**
     * Update teaching plan
     */
    public function updateTeachingPlan(UpdateTeachingPlanRequest $request, string $courseId, string $planId): JsonResponse
    {
        try {
            $tenantId = $this->getTenantId();
            $data = $request->validated();

            $plan = $this->classScheduleService->updateTeachingPlan($planId, $data, $tenantId);

            if (!$plan) {
                return $this->errorResponse(
                    message: 'Teaching plan not found',
                    code: 404
                );
            }

            return $this->successResponse(
                $plan->toArray(),
                'Teaching plan updated successfully'
            );
        } catch (\Exception $e) {
            Log::error('Error updating teaching plan', [
                'error' => $e->getMessage(),
                'course_id' => $courseId,
                'plan_id' => $planId,
                'tenant_id' => $this->getTenantId(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                message: 'Failed to update teaching plan',
                code: 500
            );
        }
    }

    /**
     * Delete teaching plan
     */
    public function deleteTeachingPlan(string $courseId, string $planId): JsonResponse
    {
        try {
            $tenantId = $this->getTenantId();
            $result = $this->classScheduleService->deleteTeachingPlan($planId, $tenantId);

            if (!$result) {
                return $this->errorResponse(
                    message: 'Teaching plan not found',
                    code: 404
                );
            }

            return $this->successResponse(
                ['deleted' => true],
                'Teaching plan deleted successfully'
            );
        } catch (\Exception $e) {
            Log::error('Error deleting teaching plan', [
                'error' => $e->getMessage(),
                'course_id' => $courseId,
                'plan_id' => $planId,
                'tenant_id' => $this->getTenantId(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                message: 'Failed to delete teaching plan',
                code: 500
            );
        }
    }

    /**
     * Bulk schedule classes from teaching plan
     */
    public function bulkScheduleClasses(BulkScheduleRequest $request, string $courseId): JsonResponse
    {
        try {
            $tenantId = $this->getTenantId();
            $data = $request->validated();
            $data['course_id'] = $courseId;

            $sessions = $this->classScheduleService->bulkScheduleClasses($data, $tenantId);

            return $this->successResponse(
                $sessions->toArray(),
                'Classes scheduled successfully',
                201
            );
        } catch (\Exception $e) {
            Log::error('Error bulk scheduling classes', [
                'error' => $e->getMessage(),
                'course_id' => $courseId,
                'tenant_id' => $this->getTenantId(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                message: 'Failed to schedule classes',
                code: 500
            );
        }
    }

    /**
     * Get tenant ID from authenticated user
     */
    private function getTenantId(): string
    {
        return Auth::user()->tenant_id;
    }
}
