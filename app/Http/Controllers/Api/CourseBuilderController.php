<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CourseBuilder\CreateModuleRequest;
use App\Http\Requests\CourseBuilder\UpdateModuleRequest;
use App\Http\Requests\CourseBuilder\CreateChapterRequest;
use App\Http\Requests\CourseBuilder\UpdateChapterRequest;
use App\Http\Requests\CourseBuilder\ReorderContentRequest;
use App\Http\Requests\CourseBuilder\UpdateCoursePricingRequest;
use App\Services\CourseBuilder\CourseBuilderService;
use App\Services\CourseBuilder\CoursePricingService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CourseBuilderController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private readonly CourseBuilderService $courseBuilderService,
        private readonly CoursePricingService $coursePricingService
    ) {}

    /**
     * Get course structure for builder
     */
    public function getCourseStructure(string $courseId): JsonResponse
    {
        try {
            $tenantId = $this->getTenantId();
            $structure = $this->courseBuilderService->getCourseStructure($courseId);
            
            return $this->successResponse(
                $structure->toArray(),
                'Course structure retrieved successfully'
            );
        } catch (\Exception $e) {
            Log::error('Error retrieving course structure', [
                'error' => $e->getMessage(),
                'course_id' => $courseId,
                'tenant_id' => $this->getTenantId(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                message: 'Failed to load course structure',
                code: 500
            );
        }
    }

    /**
     * Create module
     */
    public function createModule(CreateModuleRequest $request, string $courseId): JsonResponse
    {
        try {
            $tenantId = $this->getTenantId();
            $data = $request->validated();
            $module = $this->courseBuilderService->createModule($courseId, $data);
            
            return $this->successResponse(
                $module->toArray(),
                'Module created successfully',
                201
            );
        } catch (\Exception $e) {
            Log::error('Error creating module', [
                'error' => $e->getMessage(),
                'course_id' => $courseId,
                'tenant_id' => $this->getTenantId(),
                'data' => $request->validated(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                message: 'Failed to create module',
                code: 500
            );
        }
    }

    /**
     * Update module
     */
    public function updateModule(UpdateModuleRequest $request, string $courseId, string $moduleId): JsonResponse
    {
        try {
            $tenantId = $this->getTenantId();
            $data = $request->validated();
            $module = $this->courseBuilderService->updateModule($moduleId, $data);
            
            return $this->successResponse(
                $module->toArray(),
                'Module updated successfully'
            );
        } catch (\Exception $e) {
            Log::error('Error updating module', [
                'error' => $e->getMessage(),
                'course_id' => $courseId,
                'module_id' => $moduleId,
                'tenant_id' => $this->getTenantId(),
                'data' => $request->validated(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                message: 'Failed to update module',
                code: 500
            );
        }
    }

    /**
     * Delete module
     */
    public function deleteModule(string $courseId, string $moduleId): JsonResponse
    {
        try {
            $tenantId = $this->getTenantId();
            $this->courseBuilderService->deleteModule($moduleId);
            
            return $this->successResponse(
                [],
                'Module deleted successfully'
            );
        } catch (\Exception $e) {
            Log::error('Error deleting module', [
                'error' => $e->getMessage(),
                'course_id' => $courseId,
                'module_id' => $moduleId,
                'tenant_id' => $this->getTenantId(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                message: 'Failed to delete module',
                code: 500
            );
        }
    }

    /**
     * Create chapter
     */
    public function createChapter(CreateChapterRequest $request, string $courseId, string $moduleId): JsonResponse
    {
        try {
            $tenantId = $this->getTenantId();
            $data = $request->validated();
            $chapter = $this->courseBuilderService->createChapter($moduleId, $data);
            
            return $this->successResponse(
                $chapter->toArray(),
                'Chapter created successfully',
                201
            );
        } catch (\Exception $e) {
            Log::error('Error creating chapter', [
                'error' => $e->getMessage(),
                'course_id' => $courseId,
                'module_id' => $moduleId,
                'tenant_id' => $this->getTenantId(),
                'data' => $request->validated(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                message: 'Failed to create chapter',
                code: 500
            );
        }
    }

    /**
     * Update chapter
     */
    public function updateChapter(UpdateChapterRequest $request, string $courseId, string $moduleId, string $chapterId): JsonResponse
    {
        try {
            $tenantId = $this->getTenantId();
            $data = $request->validated();
            $chapter = $this->courseBuilderService->updateChapter($chapterId, $data);
            
            return $this->successResponse(
                $chapter->toArray(),
                'Chapter updated successfully'
            );
        } catch (\Exception $e) {
            Log::error('Error updating chapter', [
                'error' => $e->getMessage(),
                'course_id' => $courseId,
                'module_id' => $moduleId,
                'chapter_id' => $chapterId,
                'tenant_id' => $this->getTenantId(),
                'data' => $request->validated(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                message: 'Failed to update chapter',
                code: 500
            );
        }
    }

    /**
     * Delete chapter
     */
    public function deleteChapter(string $courseId, string $moduleId, string $chapterId): JsonResponse
    {
        try {
            $tenantId = $this->getTenantId();
            $this->courseBuilderService->deleteChapter($chapterId);
            
            return $this->successResponse(
                [],
                'Chapter deleted successfully'
            );
        } catch (\Exception $e) {
            Log::error('Error deleting chapter', [
                'error' => $e->getMessage(),
                'course_id' => $courseId,
                'module_id' => $moduleId,
                'chapter_id' => $chapterId,
                'tenant_id' => $this->getTenantId(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                message: 'Failed to delete chapter',
                code: 500
            );
        }
    }

    /**
     * Reorder content (modules/chapters)
     */
    public function reorderContent(ReorderContentRequest $request, string $courseId): JsonResponse
    {
        try {
            $tenantId = $this->getTenantId();
            $data = $request->validated();
            $this->courseBuilderService->reorderContent($courseId, $data);
            
            return $this->successResponse(
                [],
                'Content reordered successfully'
            );
        } catch (\Exception $e) {
            Log::error('Error reordering content', [
                'error' => $e->getMessage(),
                'course_id' => $courseId,
                'tenant_id' => $this->getTenantId(),
                'data' => $request->validated(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                message: 'Failed to reorder content',
                code: 500
            );
        }
    }

    /**
     * Get course pricing options
     */
    public function getCoursePricing(string $courseId): JsonResponse
    {
        try {
            $tenantId = $this->getTenantId();
            $pricing = $this->coursePricingService->getCoursePricing($courseId);
            
            return $this->successResponse(
                $pricing->toArray(),
                'Course pricing retrieved successfully'
            );
        } catch (\Exception $e) {
            Log::error('Error retrieving course pricing', [
                'error' => $e->getMessage(),
                'course_id' => $courseId,
                'tenant_id' => $this->getTenantId(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                message: 'Failed to load pricing information',
                code: 500
            );
        }
    }

    /**
     * Update course pricing
     */
    public function updateCoursePricing(UpdateCoursePricingRequest $request, string $courseId): JsonResponse
    {
        try {
            $tenantId = $this->getTenantId();
            $data = $request->validated();
            $pricing = $this->coursePricingService->updateCoursePricing($courseId, $data);
            
            return $this->successResponse(
                $pricing->toArray(),
                'Course pricing updated successfully'
            );
        } catch (\Exception $e) {
            Log::error('Error updating course pricing', [
                'error' => $e->getMessage(),
                'course_id' => $courseId,
                'tenant_id' => $this->getTenantId(),
                'data' => $request->validated(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                message: 'Failed to update pricing',
                code: 500
            );
        }
    }

    /**
     * Get supported course access models for tenant
     */
    public function getSupportedAccessModels(): JsonResponse
    {
        try {
            $tenantId = $this->getTenantId();
            $models = $this->coursePricingService->getSupportedAccessModels();
            
            return $this->successResponse(
                $models,
                'Access models retrieved successfully'
            );
        } catch (\Exception $e) {
            Log::error('Error retrieving access models', [
                'error' => $e->getMessage(),
                'tenant_id' => $this->getTenantId(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                message: 'Failed to load access models',
                code: 500
            );
        }
    }

    /**
     * Publish course
     */
    public function publishCourse(string $courseId): JsonResponse
    {
        try {
            $tenantId = $this->getTenantId();
            $course = $this->courseBuilderService->publishCourse($courseId);
            
            return $this->successResponse(
                $course->toArray(),
                'Course published successfully'
            );
        } catch (\Exception $e) {
            Log::error('Error publishing course', [
                'error' => $e->getMessage(),
                'course_id' => $courseId,
                'tenant_id' => $this->getTenantId(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                message: 'Failed to publish course',
                code: 500
            );
        }
    }

    /**
     * Unpublish course
     */
    public function unpublishCourse(string $courseId): JsonResponse
    {
        try {
            $tenantId = $this->getTenantId();
            $course = $this->courseBuilderService->unpublishCourse($courseId);
            
            return $this->successResponse(
                $course->toArray(),
                'Course unpublished successfully'
            );
        } catch (\Exception $e) {
            Log::error('Error unpublishing course', [
                'error' => $e->getMessage(),
                'course_id' => $courseId,
                'tenant_id' => $this->getTenantId(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                message: 'Failed to unpublish course',
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
