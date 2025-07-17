<?php

namespace App\Services\Cache;

use App\Models\Course;
use App\Models\Category;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class CourseCache
{
    protected int $defaultTtl = 3600; // 1 hour
    protected int $shortTtl = 300; // 5 minutes

    /**
     * Get courses for a tenant with caching
     */
    public function getCoursesForTenant(int $tenantId, int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        $cacheKey = "courses_tenant_{$tenantId}_page_{$page}_per_{$perPage}";
        
        return Cache::remember($cacheKey, $this->defaultTtl, function () use ($tenantId, $perPage, $page) {
            return Course::where('tenant_id', $tenantId)
                ->with(['category', 'users'])
                ->orderBy('created_at', 'desc')
                ->paginate($perPage, ['*'], 'page', $page);
        });
    }

    /**
     * Get course by ID with caching
     */
    public function getCourseById(int $courseId): ?Course
    {
        $cacheKey = "course_{$courseId}";
        
        return Cache::remember($cacheKey, $this->defaultTtl, function () use ($courseId) {
            return Course::with(['category', 'users', 'contents', 'sessions', 'exams'])
                ->find($courseId);
        });
    }

    /**
     * Get categories for a tenant with caching
     */
    public function getCategoriesForTenant(int $tenantId): Collection
    {
        $cacheKey = "categories_tenant_{$tenantId}";
        
        return Cache::remember($cacheKey, $this->defaultTtl, function () use ($tenantId) {
            return Category::where('tenant_id', $tenantId)
                ->with('children')
                ->whereNull('parent_id')
                ->orderBy('name')
                ->get();
        });
    }

    /**
     * Get student progress for a course with caching
     */
    public function getStudentProgress(int $courseId, int $studentId): ?array
    {
        $cacheKey = "student_progress_{$courseId}_{$studentId}";
        
        return Cache::remember($cacheKey, $this->shortTtl, function () use ($courseId, $studentId) {
            $course = Course::with(['contents', 'contents.studentProgress' => function ($query) use ($studentId) {
                $query->where('user_id', $studentId);
            }])->find($courseId);

            if (!$course) {
                return null;
            }

            $totalContents = $course->contents->count();
            $completedContents = $course->contents->filter(function ($content) {
                return $content->studentProgress->isNotEmpty() && 
                       $content->studentProgress->first()->status === 'completed';
            })->count();

            return [
                'course_id' => $courseId,
                'student_id' => $studentId,
                'total_contents' => $totalContents,
                'completed_contents' => $completedContents,
                'progress_percentage' => $totalContents > 0 ? round(($completedContents / $totalContents) * 100, 2) : 0,
            ];
        });
    }

    /**
     * Get enrolled students for a course with caching
     */
    public function getEnrolledStudents(int $courseId): Collection
    {
        $cacheKey = "enrolled_students_{$courseId}";
        
        return Cache::remember($cacheKey, $this->defaultTtl, function () use ($courseId) {
            return User::whereHas('courses', function ($query) use ($courseId) {
                $query->where('course_id', $courseId)->where('role', 'student');
            })->get();
        });
    }

    /**
     * Get course statistics with caching
     */
    public function getCourseStats(int $courseId): array
    {
        $cacheKey = "course_stats_{$courseId}";
        
        return Cache::remember($cacheKey, $this->shortTtl, function () use ($courseId) {
            $course = Course::with(['users', 'contents', 'sessions', 'exams'])->find($courseId);
            
            if (!$course) {
                return [];
            }

            return [
                'total_students' => $course->users()->wherePivot('role', 'student')->count(),
                'total_instructors' => $course->users()->wherePivot('role', 'instructor')->count(),
                'total_contents' => $course->contents->count(),
                'total_sessions' => $course->sessions->count(),
                'total_exams' => $course->exams->count(),
                'upcoming_sessions' => $course->sessions()->where('scheduled_at', '>', now())->count(),
                'completed_sessions' => $course->sessions()->where('scheduled_at', '<', now())->count(),
            ];
        });
    }

    /**
     * Clear course-related cache
     */
    public function clearCourseCache(int $courseId, int $tenantId): void
    {
        // Clear specific course cache
        Cache::forget("course_{$courseId}");
        Cache::forget("course_stats_{$courseId}");
        Cache::forget("enrolled_students_{$courseId}");
        
        // Clear tenant courses cache (all pages)
        $this->clearTenantCoursesCache($tenantId);
    }

    /**
     * Clear tenant courses cache
     */
    public function clearTenantCoursesCache(int $tenantId): void
    {
        // Clear paginated courses cache
        for ($page = 1; $page <= 10; $page++) { // Clear first 10 pages
            Cache::forget("courses_tenant_{$tenantId}_page_{$page}_per_15");
            Cache::forget("courses_tenant_{$tenantId}_page_{$page}_per_25");
            Cache::forget("courses_tenant_{$tenantId}_page_{$page}_per_50");
        }
        
        // Clear categories cache
        Cache::forget("categories_tenant_{$tenantId}");
    }

    /**
     * Clear student progress cache
     */
    public function clearStudentProgressCache(int $courseId, int $studentId): void
    {
        Cache::forget("student_progress_{$courseId}_{$studentId}");
    }

    /**
     * Warm up cache for a course
     */
    public function warmUpCourseCache(int $courseId): void
    {
        $course = $this->getCourseById($courseId);
        if ($course) {
            $this->getCourseStats($courseId);
            $this->getEnrolledStudents($courseId);
        }
    }
}
