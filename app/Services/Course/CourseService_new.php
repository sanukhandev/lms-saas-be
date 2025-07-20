<?php

namespace App\Services\Course;

use App\DTOs\Course\{
    CourseDTO,
    CourseStatsDTO
};
use App\Models\Course;
use App\Models\Category;
use App\Models\User;
use App\Models\StudentProgress;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Str;

class CourseService
{
    private const CACHE_TTL = 300; // 5 minutes
    private const STATS_CACHE_TTL = 600; // 10 minutes

    /**
     * Get paginated courses list with filters and enhanced data
     */
    public function getCoursesList(string $tenantId, array $filters = [], int $page = 1, int $perPage = 15): LengthAwarePaginator
    {
        $cacheKey = "courses_list_{$tenantId}_" . md5(serialize($filters) . "_{$page}_{$perPage}");
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($tenantId, $filters, $page, $perPage) {
            $query = Course::with(['category', 'instructors', 'students'])
                          ->withCount(['students as active_students_count', 'contents'])
                          ->where('tenant_id', $tenantId);

            // Apply filters
            if (!empty($filters['category_id'])) {
                $query->where('category_id', $filters['category_id']);
            }

            if (isset($filters['is_active'])) {
                $query->where('is_active', $filters['is_active']);
            }

            if (!empty($filters['search'])) {
                $query->where(function ($q) use ($filters) {
                    $q->where('title', 'like', '%' . $filters['search'] . '%')
                      ->orWhere('description', 'like', '%' . $filters['search'] . '%')
                      ->orWhere('short_description', 'like', '%' . $filters['search'] . '%');
                });
            }

            if (!empty($filters['instructor'])) {
                $query->whereHas('instructors', function ($q) use ($filters) {
                    $q->where('users.id', $filters['instructor']);
                });
            }

            if (!empty($filters['level'])) {
                $query->where('level', $filters['level']);
            }

            if (!empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            if (!empty($filters['price_min'])) {
                $query->where('price', '>=', $filters['price_min']);
            }

            if (!empty($filters['price_max'])) {
                $query->where('price', '<=', $filters['price_max']);
            }

            // Sorting
            $sortBy = $filters['sort_by'] ?? 'created_at';
            $sortOrder = $filters['sort_order'] ?? 'desc';
            
            if ($sortBy === 'enrollment_count') {
                $query->orderBy('active_students_count', $sortOrder);
            } else {
                $query->orderBy($sortBy, $sortOrder);
            }

            // Get paginated results
            $courses = $query->paginate($perPage, ['*'], 'page', $page);

            // Transform to DTOs with enhanced data
            $courseDTOs = $courses->getCollection()->map(function ($course) use ($tenantId) {
                return $this->transformCourseToEnhancedDTO($course, $tenantId);
            });

            return new LengthAwarePaginator(
                $courseDTOs,
                $courses->total(),
                $courses->perPage(),
                $courses->currentPage(),
                ['path' => Paginator::resolveCurrentPath()]
            );
        });
    }

    /**
     * Get course by ID
     */
    public function getCourseById(string $courseId, string $tenantId): ?CourseDTO
    {
        $cacheKey = "course_{$courseId}_{$tenantId}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($courseId, $tenantId) {
            $course = Course::with(['category', 'instructor', 'contents'])
                           ->where('id', $courseId)
                           ->where('tenant_id', $tenantId)
                           ->first();

            return $course ? $this->transformCourseToDTO($course, $tenantId) : null;
        });
    }

    /**
     * Create new course
     */
    public function createCourse(array $data, string $tenantId): CourseDTO
    {
        $course = DB::transaction(function () use ($data, $tenantId) {
            $courseData = [
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'short_description' => $data['short_description'] ?? null,
                'slug' => $data['slug'] ?? Str::slug($data['title']),
                'category_id' => $data['category_id'] ?? null,
                'instructor_id' => $data['instructor_id'] ?? null,
                'tenant_id' => $tenantId,
                'price' => $data['price'] ?? 0,
                'currency' => $data['currency'] ?? 'USD',
                'level' => $data['level'] ?? 'beginner',
                'duration_hours' => $data['duration_hours'] ?? null,
                'status' => $data['status'] ?? 'draft',
                'is_active' => $data['is_active'] ?? false,
                'thumbnail_url' => $data['thumbnail_url'] ?? null,
                'preview_video_url' => $data['preview_video_url'] ?? null,
                'requirements' => $data['requirements'] ?? null,
                'what_you_will_learn' => $data['what_you_will_learn'] ?? null,
                'meta_description' => $data['meta_description'] ?? null,
                'tags' => $data['tags'] ?? null,
            ];

            // Ensure slug uniqueness within tenant
            $originalSlug = $courseData['slug'];
            $counter = 1;
            while (Course::where('tenant_id', $tenantId)->where('slug', $courseData['slug'])->exists()) {
                $courseData['slug'] = $originalSlug . '-' . $counter++;
            }

            return Course::create($courseData);
        });

        // Clear relevant caches
        $this->clearCourseCaches($tenantId);

        return $this->transformCourseToDTO($course, $tenantId);
    }

    /**
     * Update course
     */
    public function updateCourse(string $courseId, array $data, string $tenantId): ?CourseDTO
    {
        $course = Course::where('id', $courseId)->where('tenant_id', $tenantId)->first();
        
        if (!$course) {
            return null;
        }

        DB::transaction(function () use ($course, $data, $tenantId) {
            $updateData = [];

            $allowedFields = [
                'title', 'description', 'short_description', 'slug', 'category_id',
                'instructor_id', 'price', 'currency', 'level', 'duration_hours',
                'status', 'is_active', 'thumbnail_url', 'preview_video_url',
                'requirements', 'what_you_will_learn', 'meta_description', 'tags'
            ];

            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updateData[$field] = $data[$field];
                }
            }

            // Handle slug uniqueness if title changes
            if (isset($data['title']) && !isset($data['slug'])) {
                $newSlug = Str::slug($data['title']);
                $originalSlug = $newSlug;
                $counter = 1;
                while (Course::where('tenant_id', $tenantId)
                            ->where('slug', $newSlug)
                            ->where('id', '!=', $course->id)
                            ->exists()) {
                    $newSlug = $originalSlug . '-' . $counter++;
                }
                $updateData['slug'] = $newSlug;
            }

            $course->update($updateData);
        });

        // Clear relevant caches
        $this->clearCourseCaches($tenantId, $courseId);

        return $this->transformCourseToDTO($course->fresh(), $tenantId);
    }

    /**
     * Delete course
     */
    public function deleteCourse(string $courseId, string $tenantId): bool
    {
        $course = Course::where('id', $courseId)->where('tenant_id', $tenantId)->first();
        
        if (!$course) {
            return false;
        }

        // Check if course has enrollments
        $hasEnrollments = $course->enrollments()->exists();
        if ($hasEnrollments) {
            throw new \Exception('Cannot delete course that has student enrollments');
        }

        DB::transaction(function () use ($course) {
            // Delete related data
            $course->contents()->delete();
            $course->studentProgress()->delete();
            
            // Delete the course
            $course->delete();
        });

        // Clear relevant caches
        $this->clearCourseCaches($tenantId, $courseId);

        return true;
    }

    /**
     * Get course statistics for dashboard
     */
    public function getCourseStats(string $tenantId): CourseStatsDTO
    {
        $cacheKey = "course_stats_{$tenantId}";
        
        return Cache::remember($cacheKey, self::STATS_CACHE_TTL, function () use ($tenantId) {
            // Total courses
            $totalCourses = Course::where('tenant_id', $tenantId)->count();
            
            // Published courses
            $publishedCourses = Course::where('tenant_id', $tenantId)
                                    ->where('status', 'published')
                                    ->count();
            
            // Draft courses
            $draftCourses = Course::where('tenant_id', $tenantId)
                                 ->where('status', 'draft')
                                 ->count();
            
            // Total active students across all courses
            $totalActiveStudents = DB::table('course_user')
                                    ->join('courses', 'course_user.course_id', '=', 'courses.id')
                                    ->where('courses.tenant_id', $tenantId)
                                    ->where('course_user.role', 'student')
                                    ->distinct('course_user.user_id')
                                    ->count();
            
            // Average completion rate
            $completionRates = Course::where('tenant_id', $tenantId)
                ->with(['students', 'studentProgress'])
                ->get()
                ->map(function ($course) {
                    $totalStudents = $course->students->count();
                    if ($totalStudents === 0) return 0;
                    
                    $completedStudents = StudentProgress::where('course_id', $course->id)
                                                       ->where('completion_percentage', 100)
                                                       ->distinct('user_id')
                                                       ->count();
                    
                    return ($completedStudents / $totalStudents) * 100;
                })
                ->filter(fn($rate) => $rate > 0);
            
            $averageCompletionRate = $completionRates->count() > 0 
                ? round($completionRates->average(), 2) 
                : 0;
            
            // Top performing courses (by enrollment)
            $topPerformingCourses = Course::where('tenant_id', $tenantId)
                ->withCount(['students as enrollment_count'])
                ->orderBy('enrollment_count', 'desc')
                ->limit(5)
                ->get()
                ->map(function ($course) use ($tenantId) {
                    return $this->transformCourseToEnhancedDTO($course, $tenantId);
                });
            
            return new CourseStatsDTO(
                totalCourses: $totalCourses,
                publishedCourses: $publishedCourses,
                draftCourses: $draftCourses,
                totalActiveStudents: $totalActiveStudents,
                averageCompletionRate: $averageCompletionRate,
                topPerformingCourses: $topPerformingCourses->toArray()
            );
        });
    }

    /**
     * Transform Course model to enhanced CourseDTO for listings
     */
    private function transformCourseToEnhancedDTO(Course $course, string $tenantId): CourseDTO
    {
        // Use already loaded counts from withCount
        $activeStudentsCount = $course->active_students_count ?? 0;
        $contentCount = $course->contents_count ?? 0;

        // Get completion rate (cached)
        $completionRate = Cache::remember(
            "course_completion_rate_{$course->id}_{$tenantId}",
            self::CACHE_TTL,
            function () use ($course, $activeStudentsCount) {
                if ($activeStudentsCount === 0) return 0;
                
                $completedEnrollments = StudentProgress::where('course_id', $course->id)
                                                     ->where('completion_percentage', 100)
                                                     ->distinct('user_id')
                                                     ->count();
                
                return round(($completedEnrollments / $activeStudentsCount) * 100, 2);
            }
        );

        // Get primary instructor
        $primaryInstructor = $course->instructors->first();

        return new CourseDTO(
            id: $course->id,
            title: $course->title,
            description: $course->description,
            shortDescription: $course->short_description ?? null,
            slug: $course->slug ?? \Illuminate\Support\Str::slug($course->title),
            categoryId: $course->category_id,
            categoryName: $course->category?->name,
            instructorId: $primaryInstructor?->id,
            instructorName: $primaryInstructor?->name,
            price: $course->price ?? 0.0,
            currency: $course->currency ?? 'USD',
            level: $course->level ?? 'beginner',
            durationHours: $course->duration_hours ?? null,
            status: $course->status ?? 'draft',
            isActive: $course->is_active,
            thumbnailUrl: $course->thumbnail_url ?? null,
            previewVideoUrl: $course->preview_video_url ?? null,
            requirements: $course->requirements ?? null,
            whatYouWillLearn: $course->what_you_will_learn ?? null,
            metaDescription: $course->meta_description ?? null,
            tags: $course->tags ?? null,
            averageRating: $course->average_rating ?? null,
            enrollmentCount: $activeStudentsCount,
            completionRate: $completionRate,
            contentCount: $contentCount,
            createdAt: $course->created_at,
            updatedAt: $course->updated_at
        );
    }

    /**
     * Transform Course model to CourseDTO
     */
    private function transformCourseToDTO(Course $course, string $tenantId): CourseDTO
    {
        // Get enrollment count (cached)
        $enrollmentCount = Cache::remember(
            "course_enrollments_{$course->id}_{$tenantId}",
            self::CACHE_TTL,
            fn() => $course->enrollments()->count()
        );

        // Get completion rate (cached)
        $completionRate = Cache::remember(
            "course_completion_rate_{$course->id}_{$tenantId}",
            self::CACHE_TTL,
            function () use ($course) {
                $totalEnrollments = $course->enrollments()->count();
                if ($totalEnrollments === 0) return 0;
                
                $completedEnrollments = StudentProgress::where('course_id', $course->id)
                                                     ->where('completion_percentage', 100)
                                                     ->distinct('user_id')
                                                     ->count();
                
                return round(($completedEnrollments / $totalEnrollments) * 100, 2);
            }
        );

        // Get content count (cached)
        $contentCount = Cache::remember(
            "course_content_count_{$course->id}_{$tenantId}",
            self::CACHE_TTL,
            fn() => $course->contents()->count()
        );

        return new CourseDTO(
            id: $course->id,
            title: $course->title,
            description: $course->description,
            shortDescription: $course->short_description ?? null,
            slug: $course->slug ?? \Illuminate\Support\Str::slug($course->title),
            categoryId: $course->category_id,
            categoryName: $course->category?->name,
            instructorId: $course->instructor_id ?? null,
            instructorName: $course->instructor?->name,
            price: $course->price ?? 0.0,
            currency: $course->currency ?? 'USD',
            level: $course->level ?? 'beginner',
            durationHours: $course->duration_hours ?? null,
            status: $course->status ?? 'draft',
            isActive: $course->is_active,
            thumbnailUrl: $course->thumbnail_url ?? null,
            previewVideoUrl: $course->preview_video_url ?? null,
            requirements: $course->requirements ?? null,
            whatYouWillLearn: $course->what_you_will_learn ?? null,
            metaDescription: $course->meta_description ?? null,
            tags: $course->tags ?? null,
            averageRating: $course->average_rating ?? null,
            enrollmentCount: $enrollmentCount,
            completionRate: $completionRate,
            contentCount: $contentCount,
            createdAt: $course->created_at,
            updatedAt: $course->updated_at
        );
    }

    /**
     * Clear course-related caches
     */
    private function clearCourseCaches(string $tenantId, ?string $courseId = null): void
    {
        // Clear stats cache
        Cache::forget("course_stats_{$tenantId}");
        
        // Clear specific course cache if provided
        if ($courseId) {
            Cache::forget("course_{$courseId}_{$tenantId}");
            Cache::forget("course_enrollments_{$courseId}_{$tenantId}");
            Cache::forget("course_completion_rate_{$courseId}_{$tenantId}");
            Cache::forget("course_content_count_{$courseId}_{$tenantId}");
        }

        // Clear list caches (pattern-based clearing)
        $keys = Cache::getRedis()->keys("*courses_list_{$tenantId}*");
        foreach ($keys as $key) {
            Cache::forget(str_replace(config('cache.prefix') . ':', '', $key));
        }
    }
}
