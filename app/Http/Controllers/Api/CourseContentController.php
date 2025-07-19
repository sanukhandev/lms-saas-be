<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CourseContent\StoreCourseContentRequest;
use App\Http\Requests\CourseContent\UpdateCourseContentRequest;
use App\Http\Requests\CourseContent\ReorderCourseContentRequest;
use App\Services\CourseContent\CourseContentService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CourseContentController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private readonly CourseContentService $courseContentService
    ) {}

    /**
     * Display a listing of course content
     */
    public function index(Request $request, int $courseId): JsonResponse
    {
        try {
            $filters = [
                'status' => $request->get('status'),
                'type' => $request->get('type'),
                'search' => $request->get('search'),
                'per_page' => $request->get('per_page', 15)
            ];

            $result = $this->courseContentService->getContentList($courseId, $filters);

            if (!$result['success']) {
                return $this->errorResponse($result['message'], [], 404);
            }

            return $this->successResponse($result['data']);

        } catch (\Exception $e) {
            Log::error('CourseContentController@index failed', [
                'course_id' => $courseId,
                'filters' => $filters ?? [],
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse('Failed to fetch course content');
        }
    }

    /**
     * Store a newly created course content
     */
    public function store(StoreCourseContentRequest $request, int $courseId): JsonResponse
    {
        try {
            $result = $this->courseContentService->createContent($courseId, $request->validated());

            if (!$result['success']) {
                return $this->errorResponse($result['message'], [], 400);
            }

            return $this->successResponse($result['data'], $result['message'], 201);

        } catch (\Exception $e) {
            Log::error('CourseContentController@store failed', [
                'course_id' => $courseId,
                'data' => $request->validated(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse('Failed to create course content');
        }
    }

    /**
     * Display the specified course content
     */
    public function show(int $courseId, int $contentId): JsonResponse
    {
        try {
            $result = $this->courseContentService->getContentById($courseId, $contentId);

            if (!$result['success']) {
                return $this->errorResponse($result['message'], [], 404);
            }

            return $this->successResponse($result['data']);

        } catch (\Exception $e) {
            Log::error('CourseContentController@show failed', [
                'course_id' => $courseId,
                'content_id' => $contentId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse('Failed to fetch course content');
        }
    }

    /**
     * Update the specified course content
     */
    public function update(UpdateCourseContentRequest $request, int $courseId, int $contentId): JsonResponse
    {
        try {
            $result = $this->courseContentService->updateContent($courseId, $contentId, $request->validated());

            if (!$result['success']) {
                return $this->errorResponse($result['message'], [], 400);
            }

            return $this->successResponse($result['data'], $result['message']);

        } catch (\Exception $e) {
            Log::error('CourseContentController@update failed', [
                'course_id' => $courseId,
                'content_id' => $contentId,
                'data' => $request->validated(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse('Failed to update course content');
        }
    }

    /**
     * Remove the specified course content
     */
    public function destroy(int $courseId, int $contentId): JsonResponse
    {
        try {
            $result = $this->courseContentService->deleteContent($courseId, $contentId);

            if (!$result['success']) {
                return $this->errorResponse($result['message'], [], 404);
            }

            return $this->successResponse([], $result['message']);

        } catch (\Exception $e) {
            Log::error('CourseContentController@destroy failed', [
                'course_id' => $courseId,
                'content_id' => $contentId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse('Failed to delete course content');
        }
    }

    /**
     * Reorder course content
     */
    public function reorder(ReorderCourseContentRequest $request, int $courseId): JsonResponse
    {
        try {
            $result = $this->courseContentService->reorderContent($courseId, $request->validated()['items']);

            if (!$result['success']) {
                return $this->errorResponse($result['message'], [], 400);
            }

            return $this->successResponse([], $result['message']);

        } catch (\Exception $e) {
            Log::error('CourseContentController@reorder failed', [
                'course_id' => $courseId,
                'items' => $request->validated()['items'],
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse('Failed to reorder course content');
        }
    }

    /**
     * Get course content statistics
     */
    public function stats(int $courseId): JsonResponse
    {
        try {
            $result = $this->courseContentService->getContentStats($courseId);

            if (!$result['success']) {
                return $this->errorResponse($result['message'], [], 404);
            }

            return $this->successResponse($result['data']);

        } catch (\Exception $e) {
            Log::error('CourseContentController@stats failed', [
                'course_id' => $courseId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse('Failed to fetch content statistics');
        }
    }
}
