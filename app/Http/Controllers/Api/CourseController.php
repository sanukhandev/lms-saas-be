<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Course\{CourseIndexRequest, CreateCourseRequest, UpdateCourseRequest};
use App\Services\Course\CourseService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CourseController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private readonly CourseService $courseService
    ) {}

    /**
     * Display a listing of courses
     */
    public function index(CourseIndexRequest $request): JsonResponse
    {
        try {
            $tenantId = $this->getTenantId();
            $filters = $request->validated();
            $page = $request->get('page', 1);
            $perPage = $request->get('per_page', 15);

            $courses = $this->courseService->getCoursesList($tenantId, $filters, $page, $perPage);

            return $this->successPaginated($courses, 'Courses retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Error retrieving courses list', [
                'error' => $e->getMessage(),
                'tenant_id' => $this->getTenantId(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                message: 'Failed to retrieve courses',
                code: 500
            );
        }
    }

    /**
     * Store a newly created course
     */
    public function store(CreateCourseRequest $request): JsonResponse
    {
        try {
            $tenantId = $this->getTenantId();
            $data = $request->validated();

            $course = $this->courseService->createCourse($data, $tenantId);

            return $this->successResponse(
                $course->toArray(),
                'Course created successfully',
                201
            );
        } catch (\Exception $e) {
            Log::error('Error creating course', [
                'error' => $e->getMessage(),
                'tenant_id' => $this->getTenantId(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                message: 'Failed to create course',
                code: 500
            );
        }
    }

    /**
     * Display the specified course
     */
    public function show(string $courseId): JsonResponse
    {
        try {
            $tenantId = $this->getTenantId();
            $course = $this->courseService->getCourseById($courseId, $tenantId);

            if (!$course) {
                return $this->errorResponse(
                    message: 'Course not found',
                    code: 404
                );
            }

            return $this->successResponse(
                $course->toArray(),
                'Course retrieved successfully'
            );
        } catch (\Exception $e) {
            Log::error('Error retrieving course', [
                'error' => $e->getMessage(),
                'course_id' => $courseId,
                'tenant_id' => $this->getTenantId(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                message: 'Failed to retrieve course',
                code: 500
            );
        }
    }

    /**
     * Update the specified course
     */
    public function update(UpdateCourseRequest $request, string $courseId): JsonResponse
    {
        try {
            $tenantId = $this->getTenantId();
            $data = $request->validated();

            $course = $this->courseService->updateCourse($courseId, $data, $tenantId);

            if (!$course) {
                return $this->errorResponse(
                    message: 'Course not found',
                    code: 404
                );
            }

            return $this->successResponse(
                $course->toArray(),
                'Course updated successfully'
            );
        } catch (\Exception $e) {
            Log::error('Error updating course', [
                'error' => $e->getMessage(),
                'course_id' => $courseId,
                'tenant_id' => $this->getTenantId(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                message: 'Failed to update course',
                code: 500
            );
        }
    }

    /**
     * Remove the specified course
     */
    public function destroy(string $courseId): JsonResponse
    {
        try {
            $tenantId = $this->getTenantId();

            $success = $this->courseService->deleteCourse($courseId, $tenantId);

            if (!$success) {
                return $this->errorResponse(
                    message: 'Course not found',
                    code: 404
                );
            }

            return $this->successResponse(
                [],
                'Course deleted successfully'
            );
        } catch (\Exception $e) {
            Log::error('Error deleting course', [
                'error' => $e->getMessage(),
                'course_id' => $courseId,
                'tenant_id' => $this->getTenantId(),
                'trace' => $e->getTraceAsString()
            ]);

            // Check for specific error messages
            if (str_contains($e->getMessage(), 'student enrollments')) {
                return $this->errorResponse(
                    message: 'Cannot delete course that has student enrollments',
                    code: 409
                );
            }

            return $this->errorResponse(
                message: 'Failed to delete course',
                code: 500
            );
        }
    }

    /**
     * Get course statistics
     */
    public function statistics(): JsonResponse
    {
        try {
            $tenantId = $this->getTenantId();
            $stats = $this->courseService->getCourseStats($tenantId);

            return $this->successResponse(
                $stats->toArray(),
                'Course statistics retrieved successfully'
            );
        } catch (\Exception $e) {
            Log::error('Error retrieving course statistics', [
                'error' => $e->getMessage(),
                'tenant_id' => $this->getTenantId(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                message: 'Failed to retrieve course statistics',
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
