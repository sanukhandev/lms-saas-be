<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateCourseContentRequest;
use App\Http\Requests\UpdateCourseContentRequest;
use App\Models\Course;
use App\Models\CourseContent;
use App\Services\CourseContentEditorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CourseContentEditorController extends Controller
{
    public function __construct(
        private CourseContentEditorService $contentService
    ) {}

    /**
     * Get course content structure
     */
    public function index(Request $request, string $courseId): JsonResponse
    {
        $filters = $request->only(['type', 'status', 'parent_id', 'search']);
        $result = $this->contentService->getCourseContent($courseId, $filters);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message']
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $result['data'],
            'course' => $result['course']
        ]);
    }

    /**
     * Create new course content
     */
    public function store(CreateCourseContentRequest $request, string $courseId): JsonResponse
    {
        $result = $this->contentService->createContent($courseId, $request->validated());

        $status = $result['success'] ? 201 : 422;

        return response()->json($result, $status);
    }

    /**
     * Get specific course content
     */
    public function show(string $courseId, string $contentId): JsonResponse
    {
        try {
            $content = CourseContent::where('id', $contentId)
                ->where('course_id', $courseId)
                ->where('tenant_id', Auth::user()->tenant_id)
                ->with(['course', 'parent', 'children', 'materials'])
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'data' => $content
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Content not found'
            ], 404);
        }
    }

    /**
     * Update course content
     */
    public function update(UpdateCourseContentRequest $request, string $courseId, string $contentId): JsonResponse
    {
        $result = $this->contentService->updateContent($contentId, $request->validated());

        $status = $result['success'] ? 200 : 422;

        return response()->json($result, $status);
    }

    /**
     * Delete course content
     */
    public function destroy(string $courseId, string $contentId): JsonResponse
    {
        $result = $this->contentService->deleteContent($contentId);

        $status = $result['success'] ? 200 : 422;

        return response()->json($result, $status);
    }

    /**
     * Reorder course content
     */
    public function reorder(Request $request, string $courseId): JsonResponse
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|integer|exists:course_contents,id',
            'items.*.sort_order' => 'required|integer|min:0',
            'items.*.position' => 'nullable|integer|min:0',
            'items.*.parent_id' => 'nullable|integer|exists:course_contents,id',
        ]);

        $result = $this->contentService->reorderContent($courseId, $request->items);

        $status = $result['success'] ? 200 : 422;

        return response()->json($result, $status);
    }

    /**
     * Duplicate course content
     */
    public function duplicate(string $courseId, string $contentId): JsonResponse
    {
        $result = $this->contentService->duplicateContent($contentId);

        $status = $result['success'] ? 201 : 422;

        return response()->json($result, $status);
    }

    /**
     * Publish/unpublish content
     */
    public function publish(Request $request, string $courseId, string $contentId): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:published,draft'
        ]);

        $result = $this->contentService->updateContent($contentId, [
            'status' => $request->status,
            'published_at' => $request->status === 'published' ? now() : null
        ]);

        $status = $result['success'] ? 200 : 422;

        return response()->json($result, $status);
    }

    /**
     * Get content statistics
     */
    public function stats(string $courseId): JsonResponse
    {
        $result = $this->contentService->getContentStats($courseId);

        $status = $result['success'] ? 200 : 422;

        return response()->json($result, $status);
    }

    /**
     * Get content types
     */
    public function contentTypes(): JsonResponse
    {
        $types = [
            'module' => [
                'label' => 'Module',
                'description' => 'A collection of related lessons',
                'icon' => 'folder',
                'can_have_children' => true
            ],
            'chapter' => [
                'label' => 'Chapter',
                'description' => 'A section within a module',
                'icon' => 'bookmark',
                'can_have_children' => true
            ],
            'lesson' => [
                'label' => 'Lesson',
                'description' => 'Individual learning unit',
                'icon' => 'book-open',
                'can_have_children' => false
            ],
            'video' => [
                'label' => 'Video',
                'description' => 'Video content',
                'icon' => 'play-circle',
                'can_have_children' => false
            ],
            'document' => [
                'label' => 'Document',
                'description' => 'PDF, DOC, or other documents',
                'icon' => 'file-text',
                'can_have_children' => false
            ],
            'quiz' => [
                'label' => 'Quiz',
                'description' => 'Interactive quiz or assessment',
                'icon' => 'help-circle',
                'can_have_children' => false
            ],
            'assignment' => [
                'label' => 'Assignment',
                'description' => 'Student assignment or project',
                'icon' => 'clipboard',
                'can_have_children' => false
            ],
            'text' => [
                'label' => 'Text Content',
                'description' => 'Rich text content',
                'icon' => 'type',
                'can_have_children' => false
            ],
            'live_session' => [
                'label' => 'Live Session',
                'description' => 'Scheduled live class',
                'icon' => 'video',
                'can_have_children' => false
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $types
        ]);
    }

    /**
     * Upload file for content
     */
    public function uploadFile(Request $request, string $courseId): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|max:102400', // 100MB
            'type' => 'nullable|string|in:video,document,image,audio'
        ]);

        try {
            // Verify course ownership
            $course = Course::where('id', $courseId)
                ->where('tenant_id', Auth::user()->tenant_id)
                ->firstOrFail();

            $file = $request->file('file');
            $path = $file->store("course-content/{$courseId}", 'public');

            return response()->json([
                'success' => true,
                'data' => [
                    'file_path' => $path,
                    'file_url' => asset('storage/' . $path),
                    'file_name' => $file->getClientOriginalName(),
                    'file_type' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                    'formatted_size' => $this->formatFileSize($file->getSize())
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'File upload failed: ' . $e->getMessage()
            ], 422);
        }
    }

    /**
     * Format file size for display
     */
    private function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }
}
