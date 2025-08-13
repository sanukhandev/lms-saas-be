<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Course\{CourseIndexRequest, CreateCourseRequest, UpdateCourseRequest};
use App\Http\Requests\CreateCourseHierarchyRequest;
use App\Services\Course\CourseService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\Course;
use Illuminate\Http\Request;

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
            // Don't find the course first - let the service handle that
            // as it checks both course ID and tenant ID together
            $tenantId = $this->getTenantId();
            $courseDto = $this->courseService->getCourseById($courseId, $tenantId);

            if (!$courseDto) {
                return $this->errorResponse(
                    message: 'Course not found',
                    code: 404
                );
            }

            return $this->successResponse(
                $courseDto->toArray(),
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
            $course = Course::findOrFail($courseId);
            $this->authorize('update', $course);
            $tenantId = $this->getTenantId();
            $data = $request->validated();

            $courseDto = $this->courseService->updateCourse($courseId, $data, $tenantId);

            if (!$courseDto) {
                return $this->errorResponse(
                    message: 'Course not found',
                    code: 404
                );
            }

            return $this->successResponse(
                $courseDto->toArray(),
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
            $course = Course::findOrFail($courseId);
            $this->authorize('delete', $course);
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
     * Get the schedule (release dates) for all modules/chapters in a course
     */
    public function getSchedule(string $courseId): JsonResponse
    {
        $course = Course::findOrFail($courseId);
        $this->authorize('view', $course);
        $schedule = $course->contents()->pluck('release_date', 'id');
        return $this->successResponse(['schedule' => $schedule], 'Schedule retrieved successfully');
    }

    /**
     * Update the schedule (release dates) for all modules/chapters in a course
     */
    public function updateSchedule(Request $request, string $courseId): JsonResponse
    {
        $course = Course::findOrFail($courseId);
        $this->authorize('update', $course);
        $data = $request->validate([
            'schedule' => 'required|array',
            'schedule.*' => 'nullable|date',
        ]);
        foreach ($data['schedule'] as $contentId => $date) {
            $content = $course->contents()->where('id', $contentId)->first();
            if ($content) {
                $content->release_date = $date;
                $content->save();
            }
        }
        return $this->successResponse([], 'Schedule updated successfully');
    }

    /**
     * Create a new hierarchy node (course, module, chapter, or class)
     */
    public function createHierarchyNode(CreateCourseHierarchyRequest $request): JsonResponse
    {
        try {
            $tenantId = $this->getTenantId();
            $data = $request->validated();
            $data['tenant_id'] = $tenantId;

            // For child nodes, inherit category_id from parent
            if (isset($data['parent_id']) && $data['parent_id'] && !isset($data['category_id'])) {
                $parent = Course::find($data['parent_id']);
                if ($parent) {
                    $data['category_id'] = $parent->category_id;
                }
            }

            // Set position if not provided
            if (!isset($data['position'])) {
                $data['position'] = $this->getNextPosition($data['parent_id'] ?? null, $data['content_type']);
            }

            $node = Course::create($data);

            return $this->successResponse(
                $node->load(['parent', 'children'])->toArray(),
                'Hierarchy node created successfully',
                201
            );
        } catch (\Exception $e) {
            Log::error('Error creating hierarchy node', [
                'error' => $e->getMessage(),
                'tenant_id' => $tenantId,
                'data' => $data,
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                message: 'Failed to create hierarchy node',
                code: 500
            );
        }
    }

    /**
     * Update an existing hierarchy node (course, module, chapter, or class)
     */
    public function updateHierarchyNode(CreateCourseHierarchyRequest $request, string $nodeId): JsonResponse
    {
        try {
            $tenantId = $this->getTenantId();
            $data = $request->validated();

            // Find the node to update
            $node = Course::where('id', $nodeId)
                ->where('tenant_id', $tenantId)
                ->first();

            if (!$node) {
                return $this->errorResponse(
                    message: 'Hierarchy node not found',
                    code: 404
                );
            }

            // Prevent changing content_type (this could break hierarchy integrity)
            if (isset($data['content_type']) && $data['content_type'] !== $node->content_type) {
                return $this->errorResponse(
                    message: 'Cannot change content type of existing node',
                    code: 400
                );
            }

            // For child nodes, inherit category_id from parent if not provided
            if (isset($data['parent_id']) && $data['parent_id'] && !isset($data['category_id'])) {
                $parent = Course::find($data['parent_id']);
                if ($parent) {
                    $data['category_id'] = $parent->category_id;
                }
            }

            // Update the node
            $node->update($data);

            return $this->successResponse(
                $node->load(['parent', 'children'])->toArray(),
                'Hierarchy node updated successfully'
            );
        } catch (\Exception $e) {
            Log::error('Error updating hierarchy node', [
                'error' => $e->getMessage(),
                'node_id' => $nodeId,
                'tenant_id' => $this->getTenantId(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                message: 'Failed to update hierarchy node',
                code: 500
            );
        }
    }

    /**
     * Get complete course hierarchy tree
     */
    public function getHierarchyTree(string $courseId): JsonResponse
    {
        try {
            $tenantId = $this->getTenantId();

            $course = Course::where('id', $courseId)
                ->where('tenant_id', $tenantId)
                ->where('content_type', 'course')
                ->first();

            if (!$course) {
                return $this->errorResponse(
                    message: 'Course not found',
                    code: 404
                );
            }

            $tree = $this->buildHierarchyTree($course);

            return $this->successResponse(
                $tree,
                'Course hierarchy retrieved successfully'
            );
        } catch (\Exception $e) {
            Log::error('Error retrieving course hierarchy', [
                'error' => $e->getMessage(),
                'course_id' => $courseId,
                'tenant_id' => $this->getTenantId(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                message: 'Failed to retrieve course hierarchy',
                code: 500
            );
        }
    }

    /**
     * Move a hierarchy node to a new parent or position
     */
    public function moveHierarchyNode(Request $request, string $nodeId): JsonResponse
    {
        try {
            $tenantId = $this->getTenantId();
            $data = $request->validate([
                'parent_id' => 'nullable|exists:courses,id',
                'position' => 'nullable|integer|min:0',
            ]);

            $node = Course::where('id', $nodeId)
                ->where('tenant_id', $tenantId)
                ->first();

            if (!$node) {
                return $this->errorResponse(
                    message: 'Node not found',
                    code: 404
                );
            }

            // Validate the move
            if (isset($data['parent_id'])) {
                $newParent = Course::find($data['parent_id']);
                if ($newParent && !$newParent->canHaveChildType($node->content_type)) {
                    return $this->errorResponse(
                        message: "A {$newParent->content_type} cannot have {$node->content_type} children.",
                        code: 400
                    );
                }
            }

            // Update the node
            if (isset($data['parent_id'])) {
                $node->parent_id = $data['parent_id'];
            }

            if (isset($data['position'])) {
                $node->position = $data['position'];
            } else {
                // Auto-assign position
                $node->position = $this->getNextPosition($node->parent_id, $node->content_type);
            }

            $node->save();

            return $this->successResponse(
                $node->load(['parent', 'children'])->toArray(),
                'Node moved successfully'
            );
        } catch (\Exception $e) {
            Log::error('Error moving hierarchy node', [
                'error' => $e->getMessage(),
                'node_id' => $nodeId,
                'tenant_id' => $this->getTenantId(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                message: 'Failed to move node',
                code: 500
            );
        }
    }

    /**
     * Get all classes for a course (from entire hierarchy)
     */
    public function getCourseClasses(string $courseId): JsonResponse
    {
        try {
            $tenantId = $this->getTenantId();

            $course = Course::where('id', $courseId)
                ->where('tenant_id', $tenantId)
                ->where('content_type', 'course')
                ->first();

            if (!$course) {
                return $this->errorResponse(
                    message: 'Course not found',
                    code: 404
                );
            }

            $classes = $course->getAllClasses()->map(function ($class) {
                return [
                    'id' => $class->id,
                    'title' => $class->title,
                    'description' => $class->description,
                    'duration_minutes' => $class->duration_minutes,
                    'video_url' => $class->video_url,
                    'hierarchy_path' => $class->getHierarchyPath(),
                    'parent' => $class->parent ? [
                        'id' => $class->parent->id,
                        'title' => $class->parent->title,
                        'content_type' => $class->parent->content_type
                    ] : null,
                ];
            });

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
     * Build complete hierarchy tree for a course
     */
    private function buildHierarchyTree(Course $course): array
    {
        $data = [
            'id' => $course->id,
            'title' => $course->title,
            'description' => $course->description,
            'content_type' => $course->content_type,
            'position' => $course->position,
            'duration_minutes' => $course->duration_minutes,
            'video_url' => $course->video_url,
            'learning_objectives' => $course->learning_objectives,
            'children' => []
        ];

        foreach ($course->children()->orderBy('position')->get() as $child) {
            $data['children'][] = $this->buildHierarchyTree($child);
        }

        return $data;
    }

    /**
     * Get next position for a new node
     */
    private function getNextPosition(?string $parentId, string $contentType): int
    {
        $query = Course::where('tenant_id', $this->getTenantId())
            ->where('content_type', $contentType);

        if ($parentId) {
            $query->where('parent_id', $parentId);
        } else {
            $query->whereNull('parent_id');
        }

        return $query->max('position') + 1;
    }

    /**
     * Get tenant ID from authenticated user
     */
    private function getTenantId(): string
    {
        return Auth::user()->tenant_id;
    }
}
