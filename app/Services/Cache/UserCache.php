<?php

namespace App\Services\Cache;

use App\Models\User;
use App\Models\Course;
use App\Models\StudentProgress;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class UserCache
{
    protected int $defaultTtl = 3600; // 1 hour
    protected int $shortTtl = 300; // 5 minutes
    protected int $longTtl = 7200; // 2 hours

    /**
     * Get user by ID with caching
     */
    public function getUserById(int $userId): ?User
    {
        $cacheKey = "user_{$userId}";
        
        return Cache::remember($cacheKey, $this->defaultTtl, function () use ($userId) {
            return User::with(['tenant', 'courses', 'studentProgress'])->find($userId);
        });
    }

    /**
     * Get users for a tenant with caching
     */
    public function getUsersForTenant(int $tenantId, int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        $cacheKey = "users_tenant_{$tenantId}_page_{$page}_per_{$perPage}";
        
        return Cache::remember($cacheKey, $this->defaultTtl, function () use ($tenantId, $perPage, $page) {
            return User::where('tenant_id', $tenantId)
                ->with(['courses'])
                ->orderBy('created_at', 'desc')
                ->paginate($perPage, ['*'], 'page', $page);
        });
    }

    /**
     * Get user dashboard data with caching
     */
    public function getUserDashboardData(int $userId): array
    {
        $cacheKey = "user_dashboard_{$userId}";
        
        return Cache::remember($cacheKey, $this->shortTtl, function () use ($userId) {
            $user = User::with(['courses', 'studentProgress'])->find($userId);
            
            if (!$user) {
                return [];
            }

            $enrolledCourses = $user->courses->count();
            $completedCourses = $user->courses->filter(function ($course) use ($userId) {
                $totalContents = $course->contents->count();
                $completedContents = $course->contents->filter(function ($content) use ($userId) {
                    return $content->studentProgress->where('user_id', $userId)->where('status', 'completed')->isNotEmpty();
                })->count();
                
                return $totalContents > 0 && $completedContents === $totalContents;
            })->count();

            $totalProgress = $user->studentProgress->sum('progress_percentage') ?? 0;
            $averageProgress = $enrolledCourses > 0 ? round($totalProgress / $enrolledCourses, 2) : 0;

            return [
                'user_id' => $userId,
                'enrolled_courses' => $enrolledCourses,
                'completed_courses' => $completedCourses,
                'in_progress_courses' => $enrolledCourses - $completedCourses,
                'average_progress' => $averageProgress,
                'total_study_time' => $user->studentProgress->sum('time_spent') ?? 0,
                'certificates_earned' => $user->certificates->count(),
                'recent_activity' => $user->studentProgress()
                    ->orderBy('updated_at', 'desc')
                    ->limit(5)
                    ->get(),
            ];
        });
    }

    /**
     * Get user's enrolled courses with caching
     */
    public function getUserEnrolledCourses(int $userId): Collection
    {
        $cacheKey = "user_enrolled_courses_{$userId}";
        
        return Cache::remember($cacheKey, $this->defaultTtl, function () use ($userId) {
            return User::find($userId)->courses()
                ->with(['category', 'contents'])
                ->wherePivot('role', 'student')
                ->orderBy('created_at', 'desc')
                ->get();
        });
    }

    /**
     * Get user's progress for a specific course with caching
     */
    public function getUserCourseProgress(int $userId, int $courseId): array
    {
        $cacheKey = "user_course_progress_{$userId}_{$courseId}";
        
        return Cache::remember($cacheKey, $this->shortTtl, function () use ($userId, $courseId) {
            $course = Course::with(['contents', 'contents.studentProgress' => function ($query) use ($userId) {
                $query->where('user_id', $userId);
            }])->find($courseId);

            if (!$course) {
                return [];
            }

            $totalContents = $course->contents->count();
            $completedContents = $course->contents->filter(function ($content) {
                return $content->studentProgress->isNotEmpty() && 
                       $content->studentProgress->first()->status === 'completed';
            })->count();

            $totalTimeSpent = $course->contents->sum(function ($content) {
                return $content->studentProgress->sum('time_spent');
            });

            return [
                'course_id' => $courseId,
                'user_id' => $userId,
                'total_contents' => $totalContents,
                'completed_contents' => $completedContents,
                'progress_percentage' => $totalContents > 0 ? round(($completedContents / $totalContents) * 100, 2) : 0,
                'time_spent' => $totalTimeSpent,
                'is_completed' => $totalContents > 0 && $completedContents === $totalContents,
                'last_activity' => $course->contents->flatMap(function ($content) {
                    return $content->studentProgress;
                })->max('updated_at'),
            ];
        });
    }

    /**
     * Get user's certificates with caching
     */
    public function getUserCertificates(int $userId): Collection
    {
        $cacheKey = "user_certificates_{$userId}";
        
        return Cache::remember($cacheKey, $this->longTtl, function () use ($userId) {
            return User::find($userId)->certificates()
                ->with(['course', 'course.category'])
                ->orderBy('issued_at', 'desc')
                ->get();
        });
    }

    /**
     * Get user's purchase history with caching
     */
    public function getUserPurchaseHistory(int $userId): Collection
    {
        $cacheKey = "user_purchases_{$userId}";
        
        return Cache::remember($cacheKey, $this->defaultTtl, function () use ($userId) {
            return User::find($userId)->coursePurchases()
                ->with(['course', 'course.category'])
                ->orderBy('purchased_at', 'desc')
                ->get();
        });
    }

    /**
     * Get instructors for a tenant with caching
     */
    public function getInstructorsForTenant(int $tenantId): Collection
    {
        $cacheKey = "instructors_tenant_{$tenantId}";
        
        return Cache::remember($cacheKey, $this->defaultTtl, function () use ($tenantId) {
            return User::where('tenant_id', $tenantId)
                ->whereHas('courses', function ($query) {
                    $query->wherePivot('role', 'instructor');
                })
                ->with(['courses' => function ($query) {
                    $query->wherePivot('role', 'instructor');
                }])
                ->get();
        });
    }

    /**
     * Get students for a tenant with caching
     */
    public function getStudentsForTenant(int $tenantId): Collection
    {
        $cacheKey = "students_tenant_{$tenantId}";
        
        return Cache::remember($cacheKey, $this->defaultTtl, function () use ($tenantId) {
            return User::where('tenant_id', $tenantId)
                ->whereHas('courses', function ($query) {
                    $query->wherePivot('role', 'student');
                })
                ->with(['courses' => function ($query) {
                    $query->wherePivot('role', 'student');
                }])
                ->get();
        });
    }

    /**
     * Clear user-related cache
     */
    public function clearUserCache(int $userId): void
    {
        Cache::forget("user_{$userId}");
        Cache::forget("user_dashboard_{$userId}");
        Cache::forget("user_enrolled_courses_{$userId}");
        Cache::forget("user_certificates_{$userId}");
        Cache::forget("user_purchases_{$userId}");
        
        // Clear course progress cache for this user
        $user = User::find($userId);
        if ($user) {
            foreach ($user->courses as $course) {
                Cache::forget("user_course_progress_{$userId}_{$course->id}");
            }
        }
    }

    /**
     * Clear tenant users cache
     */
    public function clearTenantUsersCache(int $tenantId): void
    {
        // Clear paginated users cache
        for ($page = 1; $page <= 10; $page++) { // Clear first 10 pages
            Cache::forget("users_tenant_{$tenantId}_page_{$page}_per_15");
            Cache::forget("users_tenant_{$tenantId}_page_{$page}_per_25");
            Cache::forget("users_tenant_{$tenantId}_page_{$page}_per_50");
        }
        
        Cache::forget("instructors_tenant_{$tenantId}");
        Cache::forget("students_tenant_{$tenantId}");
    }

    /**
     * Clear user course progress cache
     */
    public function clearUserCourseProgressCache(int $userId, int $courseId): void
    {
        Cache::forget("user_course_progress_{$userId}_{$courseId}");
        Cache::forget("user_dashboard_{$userId}");
    }

    /**
     * Warm up cache for a user
     */
    public function warmUpUserCache(int $userId): void
    {
        $user = $this->getUserById($userId);
        if ($user) {
            $this->getUserDashboardData($userId);
            $this->getUserEnrolledCourses($userId);
            $this->getUserCertificates($userId);
            $this->getUserPurchaseHistory($userId);
        }
    }
}
