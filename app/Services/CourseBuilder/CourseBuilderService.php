<?php

namespace App\Services\CourseBuilder;

use App\Models\Course;
use App\Models\CourseContent;
use App\DTOs\CourseBuilder\CourseStructureDTO;
use App\DTOs\CourseBuilder\ModuleDTO;
use App\DTOs\CourseBuilder\ChapterDTO;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CourseBuilderService
{
    private const CACHE_TTL = 300; // 5 minutes

    /**
     * Get complete course structure for builder
     */
    public function getCourseStructure(string $courseId): CourseStructureDTO
    {
        $tenantId = Auth::user()->tenant_id;
        $cacheKey = "course_structure_{$courseId}_{$tenantId}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($courseId, $tenantId) {
            $course = Course::with(['contents' => function ($query) {
                $query->orderBy('position');
            }])
                ->where('id', $courseId)
                ->where('tenant_id', $tenantId)
                ->firstOrFail();

            $modules = [];
            $moduleContents = $course->contents->where('type', 'module')->sortBy('position');

            foreach ($moduleContents as $module) {
                $chapters = $course->contents
                    ->where('type', 'chapter')
                    ->where('parent_id', $module->id)
                    ->sortBy('position')
                    ->map(fn($chapter) => $this->transformToChapterDTO($chapter))
                    ->values()
                    ->toArray();

                $modules[] = $this->transformToModuleDTO($module, $chapters);
            }

            return new CourseStructureDTO(
                courseId: $course->id,
                title: $course->title,
                description: $course->description,
                status: $course->status ?? 'draft',
                isActive: $course->is_active ?? false,
                modules: $modules,
                totalDuration: $this->calculateTotalDuration($course->contents),
                totalChapters: $course->contents->where('type', 'chapter')->count(),
                createdAt: $course->created_at,
                updatedAt: $course->updated_at
            );
        });
    }

    /**
     * Create a new module
     */
    public function createModule(string $courseId, array $data): ModuleDTO
    {
        $tenantId = Auth::user()->tenant_id;

        $course = Course::where('id', $courseId)
            ->where('tenant_id', $tenantId)
            ->firstOrFail();

        return DB::transaction(function () use ($course, $data) {
            // Get next position
            $nextPosition = $data['position'] ?? $this->getNextPosition($course->id, 'module');

            $module = CourseContent::create([
                'course_id' => $course->id,
                'tenant_id' => $course->tenant_id,
                'type' => 'module',
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'duration_mins' => isset($data['duration_hours']) ? $data['duration_hours'] * 60 : null,
                'position' => $nextPosition,
                'parent_id' => null,
            ]);

            $this->clearCourseCache($course->id, $course->tenant_id);

            return $this->transformToModuleDTO($module, []);
        });
    }

    /**
     * Update a module
     */
    public function updateModule(string $moduleId, array $data): ModuleDTO
    {
        $tenantId = Auth::user()->tenant_id;

        $module = CourseContent::where('id', $moduleId)
            ->where('tenant_id', $tenantId)
            ->where('type', 'module')
            ->firstOrFail();

        return DB::transaction(function () use ($module, $data) {
            $updateData = [];

            if (isset($data['title'])) {
                $updateData['title'] = $data['title'];
            }

            if (isset($data['description'])) {
                $updateData['description'] = $data['description'];
            }

            if (isset($data['duration_hours'])) {
                $updateData['duration_mins'] = $data['duration_hours'] * 60;
            }

            if (isset($data['position'])) {
                $updateData['position'] = $data['position'];
            }

            $module->update($updateData);

            $this->clearCourseCache($module->course_id, $module->tenant_id);

            // Load chapters for the response
            $chapters = CourseContent::where('parent_id', $module->id)
                ->where('type', 'chapter')
                ->orderBy('position')
                ->get()
                ->map(fn($chapter) => $this->transformToChapterDTO($chapter))
                ->toArray();

            return $this->transformToModuleDTO($module->fresh(), $chapters);
        });
    }

    /**
     * Delete a module
     */
    public function deleteModule(string $moduleId): bool
    {
        $tenantId = Auth::user()->tenant_id;

        $module = CourseContent::where('id', $moduleId)
            ->where('tenant_id', $tenantId)
            ->where('type', 'module')
            ->firstOrFail();

        return DB::transaction(function () use ($module) {
            // Delete all chapters in this module
            CourseContent::where('parent_id', $module->id)
                ->where('type', 'chapter')
                ->delete();

            // Delete the module
            $module->delete();

            $this->clearCourseCache($module->course_id, $module->tenant_id);

            return true;
        });
    }

    /**
     * Create a new chapter
     */
    public function createChapter(string $moduleId, array $data): ChapterDTO
    {
        $tenantId = Auth::user()->tenant_id;

        $module = CourseContent::where('id', $moduleId)
            ->where('tenant_id', $tenantId)
            ->where('type', 'module')
            ->firstOrFail();

        return DB::transaction(function () use ($module, $data) {
            // Get next position within the module
            $nextPosition = $data['position'] ?? $this->getNextPosition($module->course_id, 'chapter', $moduleId);

            $chapter = CourseContent::create([
                'course_id' => $module->course_id,
                'tenant_id' => $module->tenant_id,
                'parent_id' => $module->id,
                'type' => 'chapter',
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'duration_mins' => $data['duration_minutes'] ?? null,
                'position' => $nextPosition,
            ]);

            $this->clearCourseCache($module->course_id, $module->tenant_id);

            return $this->transformToChapterDTO($chapter);
        });
    }

    /**
     * Update a chapter
     */
    public function updateChapter(string $chapterId, array $data): ChapterDTO
    {
        $tenantId = Auth::user()->tenant_id;

        $chapter = CourseContent::where('id', $chapterId)
            ->where('tenant_id', $tenantId)
            ->where('type', 'chapter')
            ->firstOrFail();

        return DB::transaction(function () use ($chapter, $data) {
            $updateData = [];

            if (isset($data['title'])) {
                $updateData['title'] = $data['title'];
            }

            if (isset($data['description'])) {
                $updateData['description'] = $data['description'];
            }

            if (isset($data['duration_minutes'])) {
                $updateData['duration_mins'] = $data['duration_minutes'];
            }

            if (isset($data['position'])) {
                $updateData['position'] = $data['position'];
            }

            $chapter->update($updateData);

            $this->clearCourseCache($chapter->course_id, $chapter->tenant_id);

            return $this->transformToChapterDTO($chapter->fresh());
        });
    }

    /**
     * Delete a chapter
     */
    public function deleteChapter(string $chapterId): bool
    {
        $tenantId = Auth::user()->tenant_id;

        $chapter = CourseContent::where('id', $chapterId)
            ->where('tenant_id', $tenantId)
            ->where('type', 'chapter')
            ->firstOrFail();

        return DB::transaction(function () use ($chapter) {
            $chapter->delete();

            $this->clearCourseCache($chapter->course_id, $chapter->tenant_id);

            return true;
        });
    }

    /**
     * Reorder course content
     */
    public function reorderContent(string $courseId, array $data): bool
    {
        $tenantId = Auth::user()->tenant_id;

        $course = Course::where('id', $courseId)
            ->where('tenant_id', $tenantId)
            ->firstOrFail();

        return DB::transaction(function () use ($course, $data) {
            foreach ($data['items'] as $item) {
                CourseContent::where('id', $item['id'])
                    ->where('course_id', $course->id)
                    ->where('tenant_id', $course->tenant_id)
                    ->update([
                        'position' => $item['position'],
                        'parent_id' => $item['parent_id'] ?? null,
                    ]);
            }

            $this->clearCourseCache($course->id, $course->tenant_id);

            return true;
        });
    }

    /**
     * Publish course
     */
    public function publishCourse(string $courseId): Course
    {
        $tenantId = Auth::user()->tenant_id;

        $course = Course::where('id', $courseId)
            ->where('tenant_id', $tenantId)
            ->firstOrFail();

        // Validate course has required content
        $this->validateCourseForPublishing($course);

        $course->update([
            'status' => 'published',
            'is_active' => true,
        ]);

        $this->clearCourseCache($course->id, $course->tenant_id);

        return $course;
    }

    /**
     * Unpublish course
     */
    public function unpublishCourse(string $courseId): Course
    {
        $tenantId = Auth::user()->tenant_id;

        $course = Course::where('id', $courseId)
            ->where('tenant_id', $tenantId)
            ->firstOrFail();

        $course->update([
            'status' => 'draft',
            'is_active' => false,
        ]);

        $this->clearCourseCache($course->id, $course->tenant_id);

        return $course;
    }

    /**
     * Transform CourseContent to ModuleDTO
     */
    private function transformToModuleDTO(CourseContent $module, array $chapters): ModuleDTO
    {
        return new ModuleDTO(
            id: $module->id,
            title: $module->title,
            description: $module->description,
            position: $module->position,
            durationHours: $module->duration_mins ? round($module->duration_mins / 60, 1) : null,
            chaptersCount: count($chapters),
            chapters: $chapters,
            createdAt: $module->created_at,
            updatedAt: $module->updated_at
        );
    }

    /**
     * Transform CourseContent to ChapterDTO
     */
    private function transformToChapterDTO(CourseContent $chapter): ChapterDTO
    {
        return new ChapterDTO(
            id: $chapter->id,
            moduleId: $chapter->parent_id,
            title: $chapter->title,
            description: $chapter->description,
            position: $chapter->position,
            durationMinutes: $chapter->duration_mins,
            videoUrl: null, // Will be extended when video content is added
            content: null, // Will be extended when text content is added
            learningObjectives: [], // Will be extended when learning objectives are added
            isCompleted: false, // Will be calculated based on user progress
            createdAt: $chapter->created_at,
            updatedAt: $chapter->updated_at
        );
    }

    /**
     * Get next position for content type
     */
    private function getNextPosition(string $courseId, string $type, ?string $parentId = null): int
    {
        $query = CourseContent::where('course_id', $courseId)
            ->where('type', $type);

        if ($parentId) {
            $query->where('parent_id', $parentId);
        } else {
            $query->whereNull('parent_id');
        }

        $maxPosition = $query->max('position') ?? -1;

        return $maxPosition + 1;
    }

    /**
     * Calculate total course duration
     */
    private function calculateTotalDuration($contents): int
    {
        return $contents->sum('duration_mins') ?? 0;
    }

    /**
     * Validate course is ready for publishing
     */
    private function validateCourseForPublishing(Course $course): void
    {
        $moduleCount = $course->contents()->where('type', 'module')->count();
        $chapterCount = $course->contents()->where('type', 'chapter')->count();

        if ($moduleCount === 0) {
            throw new \Exception('Course must have at least one module to be published');
        }

        if ($chapterCount === 0) {
            throw new \Exception('Course must have at least one chapter to be published');
        }

        // Additional validation rules can be added here
    }

    /**
     * Clear course-related caches
     */
    private function clearCourseCache(string $courseId, string $tenantId): void
    {
        Cache::forget("course_structure_{$courseId}_{$tenantId}");
        Cache::forget("course_{$courseId}");
        Cache::forget("courses_list_{$tenantId}");
    }
}
