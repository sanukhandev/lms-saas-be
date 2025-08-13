<?php

namespace App\Services\Course;

use App\DTOs\Course\{CourseDTO, CourseStatsDTO};
use App\Models\{Course, Category, User, StudentProgress};
use Illuminate\Support\Facades\{DB, Cache};
use Illuminate\Pagination\{LengthAwarePaginator, Paginator};
use Illuminate\Support\Str;

class CourseService
{
    private const CACHE_TTL = 300;
    private const STATS_CACHE_TTL = 600;

    public function getCoursesList(string $tenantId, array $filters = [], int $page = 1, int $perPage = 15): LengthAwarePaginator
    {
        $cacheKey = "courses_list_{$tenantId}_" . md5(serialize($filters) . "_{$page}_{$perPage}");

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($tenantId, $filters, $page, $perPage) {
            $query = Course::with(['category', 'instructors', 'students'])
                ->withCount(['students as active_students_count', 'contents'])
                ->where('tenant_id', $tenantId);

            $this->applyFilters($query, $filters);

            $sortBy = $filters['sort_by'] ?? 'created_at';
            $sortOrder = $filters['sort_order'] ?? 'desc';

            $query->orderBy($sortBy === 'enrollment_count' ? 'active_students_count' : $sortBy, $sortOrder);

            $courses = $query->paginate($perPage, ['*'], 'page', $page);

            $courseDTOs = $courses->getCollection()->map(fn($course) => $this->transformCourseToEnhancedDTO($course, $tenantId));

            return new LengthAwarePaginator(
                $courseDTOs,
                $courses->total(),
                $courses->perPage(),
                $courses->currentPage(),
                ['path' => Paginator::resolveCurrentPath()]
            );
        });
    }

    public function getCourseById(string $courseId, string $tenantId): ?CourseDTO
    {
        $cacheKey = "course_{$courseId}_{$tenantId}";

        return Cache::tags(["tenant:{$tenantId}", "course:{$courseId}"])->remember($cacheKey, self::CACHE_TTL, function () use ($courseId, $tenantId) {
            $course = Course::with(['category', 'instructors', 'contents'])
                ->where('id', $courseId)
                ->where('tenant_id', $tenantId)
                ->first();

            return $course ? $this->transformCourseToDTO($course, $tenantId) : null;
        });
    }

    public function createCourse(array $data, string $tenantId): CourseDTO
    {
        $course = DB::transaction(function () use ($data, $tenantId) {
            $courseData = array_merge($this->defaultCourseFields($data), [
                'tenant_id' => $tenantId,
            ]);

            $courseData['slug'] = $this->generateUniqueSlug($courseData['slug'], $tenantId);

            return Course::create($courseData);
        });

        $this->clearCourseCaches($tenantId);

        return $this->transformCourseToDTO($course, $tenantId);
    }

    public function updateCourse(string $courseId, array $data, string $tenantId): ?CourseDTO
    {
        $course = Course::where('id', $courseId)->where('tenant_id', $tenantId)->first();
        if (!$course) return null;

        DB::transaction(function () use ($course, $data, $tenantId) {
            $updateData = array_intersect_key($data, array_flip($this->allowedFields()));

            if (isset($data['title']) && !isset($data['slug'])) {
                $updateData['slug'] = $this->generateUniqueSlug(Str::slug($data['title']), $tenantId, $course->id);
            }

            $course->update($updateData);
        });

        $this->clearCourseCaches($tenantId, $courseId);

        return $this->transformCourseToDTO($course->fresh(), $tenantId);
    }

    public function deleteCourse(string $courseId, string $tenantId): bool
    {
        $course = Course::where('id', $courseId)->where('tenant_id', $tenantId)->first();
        if (!$course) return false;

        if ($course->enrollments()->exists()) {
            throw new \Exception('Cannot delete course that has student enrollments');
        }

        DB::transaction(function () use ($course) {
            $course->contents()->delete();
            $course->studentProgress()->delete();
            $course->delete();
        });

        $this->clearCourseCaches($tenantId, $courseId);
        return true;
    }

    public function getCourseStats(string $tenantId): CourseStatsDTO
    {
        $cacheKey = "course_stats_{$tenantId}";

        return Cache::remember($cacheKey, self::STATS_CACHE_TTL, function () use ($tenantId) {
            $totalCourses = Course::where('tenant_id', $tenantId)->count();
            $published = Course::where('tenant_id', $tenantId)->where('status', 'published')->count();
            $drafts = Course::where('tenant_id', $tenantId)->where('status', 'draft')->count();

            $totalStudents = DB::table('course_user')
                ->join('courses', 'course_user.course_id', '=', 'courses.id')
                ->where('courses.tenant_id', $tenantId)
                ->where('course_user.role', 'student')
                ->distinct('course_user.user_id')
                ->count();

            $completionRates = Course::where('tenant_id', $tenantId)
                ->with(['students', 'studentProgress'])
                ->get()
                ->map(fn($course) => $course->students->isNotEmpty()
                    ? StudentProgress::where('course_id', $course->id)->where('completion_percentage', 100)->distinct('user_id')->count() / $course->students->count() * 100
                    : 0)
                ->filter(fn($rate) => $rate > 0);

            $avgCompletion = $completionRates->isNotEmpty() ? round($completionRates->avg(), 2) : 0;

            $topCourses = Course::where('tenant_id', $tenantId)
                ->withCount(['students as enrollment_count'])
                ->orderByDesc('enrollment_count')
                ->limit(5)
                ->get()
                ->map(fn($course) => $this->transformCourseToEnhancedDTO($course, $tenantId));

            return new CourseStatsDTO(
                totalCourses: $totalCourses,
                publishedCourses: $published,
                draftCourses: $drafts,
                totalActiveStudents: $totalStudents,
                averageCompletionRate: $avgCompletion,
                topPerformingCourses: $topCourses->toArray()
            );
        });
    }

    private function applyFilters($query, array $filters): void
    {
        $map = [
            'category_id' => 'category_id',
            'is_active' => 'is_active',
            'level' => 'level',
            'status' => 'status',
            'content_type' => 'content_type'
        ];

        foreach ($map as $key => $column) {
            if (isset($filters[$key])) {
                $query->where($column, $filters[$key]);
            }
        }

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('title', 'like', "%{$filters['search']}%")
                    ->orWhere('description', 'like', "%{$filters['search']}%")
                    ->orWhere('short_description', 'like', "%{$filters['search']}%");
            });
        }

        if (!empty($filters['instructor'])) {
            $query->whereHas('instructors', fn($q) => $q->where('users.id', $filters['instructor']));
        }

        if (!empty($filters['price_min'])) {
            $query->where('price', '>=', $filters['price_min']);
        }

        if (!empty($filters['price_max'])) {
            $query->where('price', '<=', $filters['price_max']);
        }
    }

    private function defaultCourseFields(array $data): array
    {
        return [
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'short_description' => $data['short_description'] ?? null,
            'slug' => $data['slug'] ?? Str::slug($data['title']),
            'category_id' => $data['category_id'] ?? null,
            'instructor_id' => $data['instructor_id'] ?? null,
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
    }

    private function allowedFields(): array
    {
        return array_keys($this->defaultCourseFields([]));
    }

    private function generateUniqueSlug(string $baseSlug, string $tenantId, ?string $excludeId = null): string
    {
        $slug = $baseSlug;
        $counter = 1;

        while (Course::where('tenant_id', $tenantId)->where('slug', $slug)->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))->exists()) {
            $slug = $baseSlug . '-' . $counter++;
        }

        return $slug;
    }

    private function clearCourseCaches(string $tenantId, ?string $courseId = null): void
    {
        Cache::forget("course_stats_{$tenantId}");

        if ($courseId) {
            Cache::forget("course_{$courseId}_{$tenantId}");
            Cache::forget("course_enrollments_{$courseId}_{$tenantId}");
            Cache::forget("course_completion_rate_{$courseId}_{$tenantId}");
            Cache::forget("course_content_count_{$courseId}_{$tenantId}");
        }

        $keys = Cache::getRedis()->keys("*courses_list_{$tenantId}*");
        foreach ($keys as $key) {
            Cache::forget(str_replace(config('cache.prefix') . ':', '', $key));
        }
    }

    private function transformCourseToEnhancedDTO(Course $course, string $tenantId): CourseDTO
    {
        $activeStudents = $course->active_students_count ?? 0;
        $contentCount = $course->contents_count ?? 0;

        $completionRate = Cache::remember("course_completion_rate_{$course->id}_{$tenantId}", self::CACHE_TTL, function () use ($course, $activeStudents) {
            if ($activeStudents === 0) return 0;
            $completed = StudentProgress::where('course_id', $course->id)->where('completion_percentage', 100)->distinct('user_id')->count();
            return round(($completed / $activeStudents) * 100, 2);
        });

        $primaryInstructor = $course->instructors->first();

        return $this->buildDTO($course, $tenantId, $activeStudents, $completionRate, $contentCount, $primaryInstructor);
    }

    private function transformCourseToDTO(Course $course, string $tenantId): CourseDTO
    {
        $enrollmentCount = Cache::remember("course_enrollments_{$course->id}_{$tenantId}", self::CACHE_TTL, fn() => $course->enrollments()->count());
        $completionRate = Cache::remember("course_completion_rate_{$course->id}_{$tenantId}", self::CACHE_TTL, fn() => $enrollmentCount > 0 ? round((StudentProgress::where('course_id', $course->id)->where('completion_percentage', 100)->distinct('user_id')->count() / $enrollmentCount) * 100, 2) : 0);
        $contentCount = Cache::remember("course_content_count_{$course->id}_{$tenantId}", self::CACHE_TTL, fn() => $course->contents()->count());

        return $this->buildDTO($course, $tenantId, $enrollmentCount, $completionRate, $contentCount, $course->instructor);
    }

    private function buildDTO(Course $course, string $tenantId, int $enrollmentCount, float $completionRate, int $contentCount, ?User $instructor): CourseDTO
    {
        return new CourseDTO(
            id: $course->id,
            title: $course->title,
            description: $course->description,
            shortDescription: $course->short_description,
            slug: $course->slug ?? Str::slug($course->title),
            categoryId: $course->category_id,
            categoryName: $course->category?->name,
            instructorId: $instructor?->id,
            instructorName: $instructor?->name,
            price: $course->price,
            currency: $course->currency,
            level: $course->level,
            durationHours: $course->duration_hours,
            status: $course->status,
            isActive: $course->is_active,
            thumbnailUrl: $course->thumbnail_url,
            previewVideoUrl: $course->preview_video_url,
            requirements: $course->requirements,
            whatYouWillLearn: $course->what_you_will_learn,
            metaDescription: $course->meta_description,
            tags: $course->tags,
            averageRating: $course->average_rating,
            enrollmentCount: $enrollmentCount,
            completionRate: $completionRate,
            contentCount: $contentCount,
            createdAt: $course->created_at,
            updatedAt: $course->updated_at
        );
    }
}
