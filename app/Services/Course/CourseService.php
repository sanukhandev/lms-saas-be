<?php

namespace App\Services\Course;

use App\Models\Course;
use App\Models\User;
use App\Models\StudentProgress;
use App\Services\Cache\BaseCacheService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class CourseService extends BaseCacheService
{
    /**
     * Get course by ID with caching
     */
    public function getCourseById(int $courseId, bool $withRelations = true): ?Course
    {
        $cacheKey = $this->getCourseCacheKey('course_detail', $courseId, $withRelations ? 'with_relations' : 'basic');

        return Cache::remember($cacheKey, $this->defaultTtl, function () use ($courseId, $withRelations) {
            $query = Course::query();

            if ($withRelations) {
                $query->with(['category', 'instructor', 'contents', 'users']);
            }

            return $query->find($courseId);
        });
    }

    /**
     * Get courses for a tenant with caching
     */
    public function getCoursesForTenant(int $tenantId, array $filters = []): Collection
    {
        $cacheKey = $this->getTenantCacheKey('courses', $tenantId, md5(serialize($filters)));

        return Cache::remember($cacheKey, $this->defaultTtl, function () use ($tenantId, $filters) {
            $query = Course::where('tenant_id', $tenantId)
                ->with(['category', 'instructor']);

            if (isset($filters['status'])) {
                $query->where('is_active', $filters['status'] === 'active');
            }

            if (isset($filters['category_id'])) {
                $query->where('category_id', $filters['category_id']);
            }

            if (isset($filters['instructor_id'])) {
                $query->where('instructor_id', $filters['instructor_id']);
            }

            return $query->orderBy('created_at', 'desc')->get();
        });
    }

    /**
     * Get course progress for a user with caching
     */
    public function getUserCourseProgress(int $userId, int $courseId): ?StudentProgress
    {
        $cacheKey = $this->getUserCacheKey('course_progress', $userId, "course_{$courseId}");

        return Cache::remember($cacheKey, $this->shortTtl, function () use ($userId, $courseId) {
            return StudentProgress::where('user_id', $userId)
                ->where('course_id', $courseId)
                ->with(['course', 'user'])
                ->first();
        });
    }

    /**
     * Get course enrollment count with caching
     */
    public function getCourseEnrollmentCount(int $courseId): int
    {
        $cacheKey = $this->getCourseCacheKey('enrollment_count', $courseId);

        return Cache::remember($cacheKey, $this->defaultTtl, function () use ($courseId) {
            return Course::find($courseId)
                ->users()
                ->wherePivot('role', 'student')
                ->count();
        });
    }

    /**
     * Get course completion rate with caching
     */
    public function getCourseCompletionRate(int $courseId): float
    {
        $cacheKey = $this->getCourseCacheKey('completion_rate', $courseId);

        return Cache::remember($cacheKey, $this->defaultTtl, function () use ($courseId) {
            $totalEnrollments = $this->getCourseEnrollmentCount($courseId);

            if ($totalEnrollments === 0) {
                return 0.0;
            }

            $completedCount = StudentProgress::where('course_id', $courseId)
                ->where('completion_percentage', 100)
                ->count();

            return ($completedCount / $totalEnrollments) * 100;
        });
    }

    /**
     * Get popular courses for a tenant with caching
     */
    public function getPopularCourses(int $tenantId, int $limit = 10): Collection
    {
        $cacheKey = $this->getTenantCacheKey('popular_courses', $tenantId, "limit_{$limit}");

        return Cache::remember($cacheKey, $this->longTtl, function () use ($tenantId, $limit) {
            return Course::where('tenant_id', $tenantId)
                ->withCount([
                    'users as enrollments_count' => function ($query) {
                        $query->wherePivot('role', 'student');
                    }
                ])
                ->orderBy('enrollments_count', 'desc')
                ->limit($limit)
                ->get();
        });
    }

    /**
     * Clear cache for a specific course
     */
    public function clearCourseCache(int $courseId): void
    {
        $this->clearCacheByPattern("course_{$courseId}");
    }

    /**
     * Clear cache for a tenant's courses
     */
    public function clearTenantCoursesCache(int $tenantId): void
    {
        $this->clearCacheByPattern("courses_tenant_{$tenantId}");
        $this->clearCacheByPattern("popular_courses_tenant_{$tenantId}");
    }

    /**
     * Clear cache for user's course progress
     */
    public function clearUserCourseProgress(int $userId, int $courseId = null): void
    {
        if ($courseId) {
            $this->clearCacheByKey($this->getUserCacheKey('course_progress', $userId, "course_{$courseId}"));
        } else {
            $this->clearCacheByPattern("course_progress_user_{$userId}");
        }
    }

    /**
     * Warm cache for important course data
     */
    public function warmCourseCache(int $courseId): void
    {
        $this->getCourseById($courseId, true);
        $this->getCourseEnrollmentCount($courseId);
        $this->getCourseCompletionRate($courseId);
    }
}
