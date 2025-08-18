<?php

namespace App\Services;

use App\DTOs\CourseContentEditorDTO;
use App\Models\Course;
use App\Models\CourseContent;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CourseContentEditorService
{
    /**
     * Create new course content
     */
    public function createContent(int $courseId, array $data): array
    {
        try {
            DB::beginTransaction();

            // Verify course belongs to current tenant
            $course = Course::where('id', $courseId)
                ->where('tenant_id', Auth::user()->tenant_id)
                ->firstOrFail();

            // Handle file upload if present
            if (isset($data['file']) && $data['file'] instanceof UploadedFile) {
                $fileData = $this->handleFileUpload($data['file'], $courseId);
                $data = array_merge($data, $fileData);
                unset($data['file']);
            }

            // Set sort order if not provided
            if (!isset($data['sort_order'])) {
                $maxOrder = CourseContent::where('course_id', $courseId)
                    ->where('parent_id', $data['parent_id'] ?? null)
                    ->max('sort_order') ?? 0;
                $data['sort_order'] = $maxOrder + 1;
            }

            // Set position if not provided
            if (!isset($data['position'])) {
                $maxPosition = CourseContent::where('course_id', $courseId)
                    ->where('parent_id', $data['parent_id'] ?? null)
                    ->max('position') ?? 0;
                $data['position'] = $maxPosition + 1;
            }

            // Auto-publish if specified
            if (($data['auto_publish'] ?? false) && $data['status'] === 'published') {
                $data['published_at'] = now();
            }

            $data['course_id'] = $courseId;
            $data['tenant_id'] = Auth::user()->tenant_id;

            $content = CourseContent::create($data);

            DB::commit();

            return [
                'success' => true,
                'message' => 'Content created successfully',
                'data' => CourseContentEditorDTO::fromModel(
                    $content->load(['course', 'parent', 'children'])
                )->toArray()
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create course content', [
                'course_id' => $courseId,
                'data' => $data,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to create content: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Update existing course content
     */
    public function updateContent(int $contentId, array $data): array
    {
        try {
            DB::beginTransaction();

            $content = CourseContent::where('id', $contentId)
                ->where('tenant_id', Auth::user()->tenant_id)
                ->firstOrFail();

            // Handle file upload if present
            if (isset($data['file']) && $data['file'] instanceof UploadedFile) {
                // Delete old file if exists
                if ($content->file_path) {
                    Storage::disk('public')->delete($content->file_path);
                }

                $fileData = $this->handleFileUpload($data['file'], $content->course_id);
                $data = array_merge($data, $fileData);
                unset($data['file']);
            }

            // Handle publishing
            if (isset($data['status']) && $data['status'] === 'published' && !$content->published_at) {
                $data['published_at'] = now();
            } elseif (isset($data['status']) && $data['status'] !== 'published') {
                $data['published_at'] = null;
            }

            $content->update($data);

            DB::commit();

            return [
                'success' => true,
                'message' => 'Content updated successfully',
                'data' => CourseContentEditorDTO::fromModel(
                    $content->fresh()->load(['course', 'parent', 'children'])
                )->toArray()
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update course content', [
                'content_id' => $contentId,
                'data' => $data,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to update content: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Delete course content
     */
    public function deleteContent(int $contentId): array
    {
        try {
            DB::beginTransaction();

            $content = CourseContent::where('id', $contentId)
                ->where('tenant_id', Auth::user()->tenant_id)
                ->firstOrFail();

            // Delete associated file if exists
            if ($content->file_path) {
                Storage::disk('public')->delete($content->file_path);
            }

            // Delete content and all children (cascade)
            $content->delete();

            DB::commit();

            return [
                'success' => true,
                'message' => 'Content deleted successfully'
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete course content', [
                'content_id' => $contentId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to delete content: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Reorder course content
     */
    public function reorderContent(int $courseId, array $orderData): array
    {
        try {
            DB::beginTransaction();

            $course = Course::where('id', $courseId)
                ->where('tenant_id', Auth::user()->tenant_id)
                ->firstOrFail();

            foreach ($orderData as $item) {
                CourseContent::where('id', $item['id'])
                    ->where('course_id', $courseId)
                    ->update([
                        'sort_order' => $item['sort_order'],
                        'position' => $item['position'] ?? $item['sort_order'],
                        'parent_id' => $item['parent_id'] ?? null
                    ]);
            }

            DB::commit();

            return [
                'success' => true,
                'message' => 'Content reordered successfully'
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to reorder course content', [
                'course_id' => $courseId,
                'order_data' => $orderData,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to reorder content: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get course content structure
     */
    public function getCourseContent(int $courseId, array $filters = []): array
    {
        try {
            $course = Course::where('id', $courseId)
                ->where('tenant_id', Auth::user()->tenant_id)
                ->firstOrFail();

            $query = CourseContent::where('course_id', $courseId)
                ->with(['parent', 'children']);

            // Apply filters
            if (isset($filters['type'])) {
                $query->where('type', $filters['type']);
            }

            if (isset($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            if (isset($filters['parent_id'])) {
                $query->where('parent_id', $filters['parent_id']);
            }

            if (isset($filters['search'])) {
                $query->where(function ($q) use ($filters) {
                    $q->where('title', 'like', '%' . $filters['search'] . '%')
                        ->orWhere('description', 'like', '%' . $filters['search'] . '%');
                });
            }

            $contents = $query->orderBy('sort_order')->get();

            // Convert to DTOs
            $contentDTOs = $contents->map(
                fn($content) =>
                CourseContentEditorDTO::fromModel($content)
            );

            // Build tree structure if no parent_id filter
            if (!isset($filters['parent_id'])) {
                $contentDTOs = $this->buildContentTree($contentDTOs);
            }

            return [
                'success' => true,
                'data' => $contentDTOs->map(fn($dto) => $dto->toArray())->toArray(),
                'course' => $course
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get course content', [
                'course_id' => $courseId,
                'filters' => $filters,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to retrieve content: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Duplicate content
     */
    public function duplicateContent(int $contentId): array
    {
        try {
            DB::beginTransaction();

            $original = CourseContent::where('id', $contentId)
                ->where('tenant_id', Auth::user()->tenant_id)
                ->firstOrFail();

            $duplicate = $original->replicate();
            $duplicate->title = $original->title . ' (Copy)';
            $duplicate->status = 'draft';
            $duplicate->published_at = null;

            // Handle file duplication
            if ($original->file_path) {
                $newFilePath = $this->duplicateFile($original->file_path, $original->course_id);
                $duplicate->file_path = $newFilePath;
            }

            // Set new sort order
            $maxOrder = CourseContent::where('course_id', $original->course_id)
                ->where('parent_id', $original->parent_id)
                ->max('sort_order') ?? 0;
            $duplicate->sort_order = $maxOrder + 1;
            $duplicate->position = $maxOrder + 1;

            $duplicate->save();

            DB::commit();

            return [
                'success' => true,
                'message' => 'Content duplicated successfully',
                'data' => CourseContentEditorDTO::fromModel(
                    $duplicate->load(['course', 'parent'])
                )->toArray()
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to duplicate course content', [
                'content_id' => $contentId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to duplicate content: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Handle file upload
     */
    private function handleFileUpload(UploadedFile $file, int $courseId): array
    {
        $path = $file->store("course-content/{$courseId}", 'public');

        return [
            'file_path' => $path,
            'file_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
        ];
    }

    /**
     * Duplicate file
     */
    private function duplicateFile(string $originalPath, int $courseId): string
    {
        $extension = pathinfo($originalPath, PATHINFO_EXTENSION);
        $filename = pathinfo($originalPath, PATHINFO_FILENAME);
        $newFilename = $filename . '_copy_' . time() . '.' . $extension;
        $newPath = "course-content/{$courseId}/{$newFilename}";

        Storage::disk('public')->copy($originalPath, $newPath);

        return $newPath;
    }

    /**
     * Build hierarchical tree structure from DTOs
     */
    private function buildContentTree($contentDTOs, $parentId = null): array
    {
        $tree = [];

        foreach ($contentDTOs as $contentDTO) {
            if ($contentDTO->parentId == $parentId) {
                $children = $this->buildContentTree($contentDTOs, $contentDTO->id);
                // Create new DTO with children
                $tree[] = new CourseContentEditorDTO(
                    id: $contentDTO->id,
                    courseId: $contentDTO->courseId,
                    parentId: $contentDTO->parentId,
                    type: $contentDTO->type,
                    title: $contentDTO->title,
                    description: $contentDTO->description,
                    content: $contentDTO->content,
                    contentData: $contentDTO->contentData,
                    videoUrl: $contentDTO->videoUrl,
                    filePath: $contentDTO->filePath,
                    fileUrl: $contentDTO->fileUrl,
                    fileType: $contentDTO->fileType,
                    fileSize: $contentDTO->fileSize,
                    formattedFileSize: $contentDTO->formattedFileSize,
                    learningObjectives: $contentDTO->learningObjectives,
                    status: $contentDTO->status,
                    isRequired: $contentDTO->isRequired,
                    isFree: $contentDTO->isFree,
                    position: $contentDTO->position,
                    sortOrder: $contentDTO->sortOrder,
                    durationMins: $contentDTO->durationMins,
                    estimatedDuration: $contentDTO->estimatedDuration,
                    formattedDuration: $contentDTO->formattedDuration,
                    publishedAt: $contentDTO->publishedAt,
                    contentTypeIcon: $contentDTO->contentTypeIcon,
                    hierarchyLevel: $contentDTO->hierarchyLevel,
                    createdAt: $contentDTO->createdAt,
                    updatedAt: $contentDTO->updatedAt,
                    children: collect($children),
                    parent: $contentDTO->parent,
                );
            }
        }

        return $tree;
    }

    /**
     * Get content statistics
     */
    public function getContentStats(int $courseId): array
    {
        try {
            $course = Course::where('id', $courseId)
                ->where('tenant_id', Auth::user()->tenant_id)
                ->firstOrFail();

            $stats = [
                'total_content' => CourseContent::where('course_id', $courseId)->count(),
                'published_content' => CourseContent::where('course_id', $courseId)
                    ->where('status', 'published')->count(),
                'draft_content' => CourseContent::where('course_id', $courseId)
                    ->where('status', 'draft')->count(),
                'total_duration' => CourseContent::where('course_id', $courseId)
                    ->sum('estimated_duration') ?? 0,
                'content_by_type' => CourseContent::where('course_id', $courseId)
                    ->select('type', DB::raw('count(*) as count'))
                    ->groupBy('type')
                    ->pluck('count', 'type')
                    ->toArray(),
                'required_content' => CourseContent::where('course_id', $courseId)
                    ->where('is_required', true)->count(),
                'free_content' => CourseContent::where('course_id', $courseId)
                    ->where('is_free', true)->count(),
            ];

            return [
                'success' => true,
                'data' => $stats
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get content stats', [
                'course_id' => $courseId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to retrieve stats: ' . $e->getMessage()
            ];
        }
    }
}
