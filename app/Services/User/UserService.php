<?php

namespace App\Services\User;

use App\DTOs\User\{
    UserDTO,
    UserStatsDTO,
    UserListDTO
};
use App\Models\User;
use App\Models\Course;
use App\Models\StudentProgress;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Carbon\Carbon;

class UserService
{
    private const CACHE_TTL = 300; // 5 minutes
    private const STATS_CACHE_TTL = 600; // 10 minutes

    /**
     * Get paginated users list with filters
     */
    public function getUsersList(string $tenantId, array $filters = [], int $page = 1, int $perPage = 15): LengthAwarePaginator
    {
        $cacheKey = "users_list_{$tenantId}_" . md5(serialize($filters) . "_{$page}_{$perPage}");
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($tenantId, $filters, $page, $perPage) {
            $query = User::where('tenant_id', $tenantId);

            // Apply filters
            if (!empty($filters['role'])) {
                $query->where('role', $filters['role']);
            }

            if (!empty($filters['search'])) {
                $query->where(function ($q) use ($filters) {
                    $q->where('name', 'like', '%' . $filters['search'] . '%')
                      ->orWhere('email', 'like', '%' . $filters['search'] . '%');
                });
            }

            if (isset($filters['verified'])) {
                if ($filters['verified']) {
                    $query->whereNotNull('email_verified_at');
                } else {
                    $query->whereNull('email_verified_at');
                }
            }

            if (!empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            // Sorting
            $sortBy = $filters['sort_by'] ?? 'created_at';
            $sortOrder = $filters['sort_order'] ?? 'desc';
            $query->orderBy($sortBy, $sortOrder);

            // Get paginated results
            $users = $query->paginate($perPage, ['*'], 'page', $page);

            // Transform to DTOs
            $userDTOs = $users->getCollection()->map(function ($user) use ($tenantId) {
                return $this->transformUserToDTO($user, $tenantId);
            });

            return new LengthAwarePaginator(
                $userDTOs,
                $users->total(),
                $users->perPage(),
                $users->currentPage(),
                ['path' => Paginator::resolveCurrentPath()]
            );
        });
    }

    /**
     * Get user by ID
     */
    public function getUserById(string $userId, string $tenantId): ?UserDTO
    {
        $cacheKey = "user_{$userId}_{$tenantId}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($userId, $tenantId) {
            $user = User::where('id', $userId)
                       ->where('tenant_id', $tenantId)
                       ->first();

            return $user ? $this->transformUserToDTO($user, $tenantId) : null;
        });
    }

    /**
     * Create new user
     */
    public function createUser(array $data, string $tenantId): UserDTO
    {
        $user = DB::transaction(function () use ($data, $tenantId) {
            $userData = [
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'role' => $data['role'] ?? 'student',
                'tenant_id' => $tenantId,
                'status' => $data['status'] ?? 'active',
                'email_verified_at' => $data['email_verified'] ?? false ? now() : null,
            ];

            return User::create($userData);
        });

        // Clear relevant caches
        $this->clearUserCaches($tenantId);

        return $this->transformUserToDTO($user, $tenantId);
    }

    /**
     * Update user
     */
    public function updateUser(string $userId, array $data, string $tenantId): ?UserDTO
    {
        $user = User::where('id', $userId)->where('tenant_id', $tenantId)->first();
        
        if (!$user) {
            return null;
        }

        DB::transaction(function () use ($user, $data) {
            $updateData = [];

            if (isset($data['name'])) {
                $updateData['name'] = $data['name'];
            }

            if (isset($data['email'])) {
                $updateData['email'] = $data['email'];
            }

            if (isset($data['password'])) {
                $updateData['password'] = Hash::make($data['password']);
            }

            if (isset($data['role'])) {
                $updateData['role'] = $data['role'];
            }

            if (isset($data['status'])) {
                $updateData['status'] = $data['status'];
            }

            if (isset($data['email_verified'])) {
                $updateData['email_verified_at'] = $data['email_verified'] ? now() : null;
            }

            $user->update($updateData);
        });

        // Clear relevant caches
        $this->clearUserCaches($tenantId, $userId);

        return $this->transformUserToDTO($user->fresh(), $tenantId);
    }

    /**
     * Delete user
     */
    public function deleteUser(string $userId, string $tenantId): bool
    {
        $user = User::where('id', $userId)->where('tenant_id', $tenantId)->first();
        
        if (!$user) {
            return false;
        }

        DB::transaction(function () use ($user) {
            // Soft delete or handle related data as needed
            $user->delete();
        });

        // Clear relevant caches
        $this->clearUserCaches($tenantId, $userId);

        return true;
    }

    /**
     * Get user statistics
     */
    public function getUserStats(string $tenantId): UserStatsDTO
    {
        $cacheKey = "user_stats_{$tenantId}";
        
        return Cache::remember($cacheKey, self::STATS_CACHE_TTL, function () use ($tenantId) {
            $totalUsers = User::where('tenant_id', $tenantId)->count();
            $activeUsers = User::where('tenant_id', $tenantId)
                              ->where('status', 'active')
                              ->count();
            $verifiedUsers = User::where('tenant_id', $tenantId)
                                ->whereNotNull('email_verified_at')
                                ->count();

            // Users by role
            $usersByRole = User::where('tenant_id', $tenantId)
                              ->groupBy('role')
                              ->selectRaw('role, count(*) as count')
                              ->pluck('count', 'role')
                              ->toArray();

            // Recent registrations (last 30 days)
            $recentRegistrations = User::where('tenant_id', $tenantId)
                                      ->where('created_at', '>=', Carbon::now()->subDays(30))
                                      ->count();

            // User growth rate
            $previousMonthUsers = User::where('tenant_id', $tenantId)
                                     ->where('created_at', '<', Carbon::now()->subDays(30))
                                     ->count();
            
            $growthRate = $previousMonthUsers > 0 
                ? (($recentRegistrations / $previousMonthUsers) * 100) 
                : 0;

            return new UserStatsDTO(
                totalUsers: $totalUsers,
                activeUsers: $activeUsers,
                verifiedUsers: $verifiedUsers,
                usersByRole: $usersByRole,
                recentRegistrations: $recentRegistrations,
                growthRate: round($growthRate, 2)
            );
        });
    }

    /**
     * Transform User model to UserDTO
     */
    private function transformUserToDTO(User $user, string $tenantId): UserDTO
    {
        // Get user's course enrollment count (cached)
        $enrollmentCount = Cache::remember(
            "user_enrollments_{$user->id}_{$tenantId}",
            self::CACHE_TTL,
            fn() => $user->enrolledCourses()->count()
        );

        // Get user's completed courses count (cached)
        $completedCourses = Cache::remember(
            "user_completed_courses_{$user->id}_{$tenantId}",
            self::CACHE_TTL,
            function () use ($user, $tenantId) {
                return StudentProgress::where('user_id', $user->id)
                    ->where('tenant_id', $tenantId)
                    ->where('completion_percentage', 100)
                    ->distinct('course_id')
                    ->count();
            }
        );

        return new UserDTO(
            id: $user->id,
            name: $user->name,
            email: $user->email,
            role: $user->role,
            status: $user->status,
            emailVerified: !is_null($user->email_verified_at),
            enrollmentCount: $enrollmentCount,
            completedCourses: $completedCourses,
            lastLoginAt: $user->last_login_at,
            createdAt: $user->created_at,
            updatedAt: $user->updated_at
        );
    }

    /**
     * Clear user-related caches
     */
    private function clearUserCaches(string $tenantId, ?string $userId = null): void
    {
        // Clear list caches
        Cache::forget("user_stats_{$tenantId}");
        
        // Clear specific user cache if provided
        if ($userId) {
            Cache::forget("user_{$userId}_{$tenantId}");
            Cache::forget("user_enrollments_{$userId}_{$tenantId}");
            Cache::forget("user_completed_courses_{$userId}_{$tenantId}");
        }

        // Clear list caches (this is a bit aggressive, but ensures consistency)
        $tags = ["users_list_{$tenantId}"];
        foreach ($tags as $tag) {
            // In a real implementation, you might want to use cache tags
            // For now, we'll clear based on pattern
            $keys = Cache::getRedis()->keys("*users_list_{$tenantId}*");
            foreach ($keys as $key) {
                Cache::forget(str_replace(config('cache.prefix') . ':', '', $key));
            }
        }
    }
}
