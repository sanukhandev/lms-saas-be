<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Models\Tenant;
use App\Services\Cache\BaseCacheService;
use Illuminate\Support\Facades\Cache;

class AuthCacheService extends BaseCacheService
{
    /**
     * Get user with caching
     */
    public function getUserById(int $userId): ?User
    {
        $cacheKey = $this->getUserCacheKey('user_detail', $userId);

        return Cache::remember($cacheKey, $this->defaultTtl, function () use ($userId) {
            return User::with(['tenant'])->find($userId);
        });
    }

    /**
     * Get user by email with caching
     */
    public function getUserByEmail(string $email, int $tenantId = null): ?User
    {
        $cacheKey = $tenantId
            ? "user_email_{$email}_tenant_{$tenantId}"
            : "user_email_{$email}";

        return Cache::remember($cacheKey, $this->defaultTtl, function () use ($email, $tenantId) {
            $query = User::where('email', $email);

            if ($tenantId) {
                $query->where('tenant_id', $tenantId);
            }

            return $query->with(['tenant'])->first();
        });
    }

    /**
     * Get tenant by slug with caching
     */
    public function getTenantBySlug(string $slug): ?Tenant
    {
        $cacheKey = "tenant_slug_{$slug}";

        return Cache::remember($cacheKey, $this->veryLongTtl, function () use ($slug) {
            return Tenant::where('slug', $slug)
                ->where('status', 'active')
                ->first();
        });
    }

    /**
     * Get tenant by domain with caching
     */
    public function getTenantByDomain(string $domain): ?Tenant
    {
        $cacheKey = "tenant_domain_{$domain}";

        return Cache::remember($cacheKey, $this->veryLongTtl, function () use ($domain) {
            return Tenant::where('domain', $domain)
                ->where('status', 'active')
                ->first();
        });
    }

    /**
     * Get user permissions with caching (simplified - uses role)
     */
    public function getUserPermissions(int $userId): array
    {
        $cacheKey = $this->getUserCacheKey('permissions', $userId);

        return Cache::remember($cacheKey, $this->longTtl, function () use ($userId) {
            $user = User::find($userId);

            if (!$user) {
                return [];
            }

            // Return basic permissions based on role
            $permissions = [];
            switch ($user->role) {
                case 'super_admin':
                    $permissions = ['*']; // All permissions
                    break;
                case 'admin':
                    $permissions = ['manage_users', 'manage_courses', 'view_reports'];
                    break;
                case 'staff':
                    $permissions = ['manage_courses', 'view_reports'];
                    break;
                case 'tutor':
                    $permissions = ['manage_own_courses', 'view_students'];
                    break;
                case 'student':
                    $permissions = ['view_courses', 'take_courses'];
                    break;
            }

            return $permissions;
        });
    }

    /**
     * Get user roles with caching (simplified - single role)
     */
    public function getUserRoles(int $userId): array
    {
        $cacheKey = $this->getUserCacheKey('roles', $userId);

        return Cache::remember($cacheKey, $this->longTtl, function () use ($userId) {
            $user = User::find($userId);

            return $user ? [$user->role] : [];
        });
    }

    /**
     * Check if user has permission with caching
     */
    public function userHasPermission(int $userId, string $permission): bool
    {
        $permissions = $this->getUserPermissions($userId);

        return in_array($permission, $permissions);
    }

    /**
     * Check if user has role with caching
     */
    public function userHasRole(int $userId, string $role): bool
    {
        $roles = $this->getUserRoles($userId);

        return in_array($role, $roles);
    }

    /**
     * Clear user cache
     */
    public function clearUserCache(int $userId): void
    {
        $patterns = [
            "user_detail_user_{$userId}",
            "permissions_user_{$userId}",
            "roles_user_{$userId}",
        ];

        foreach ($patterns as $pattern) {
            $this->clearCacheByKey($pattern);
        }

        // Clear email-based cache if we have user data
        $user = User::find($userId);
        if ($user) {
            $this->clearCacheByKey("user_email_{$user->email}");
            $this->clearCacheByKey("user_email_{$user->email}_tenant_{$user->tenant_id}");
        }
    }

    /**
     * Clear tenant cache
     */
    public function clearTenantCache(int $tenantId): void
    {
        $tenant = Tenant::find($tenantId);
        if ($tenant) {
            $this->clearCacheByKey("tenant_slug_{$tenant->slug}");
            $this->clearCacheByKey("tenant_domain_{$tenant->domain}");
        }
    }

    /**
     * Clear all authentication related cache
     */
    public function clearAllAuthCache(): void
    {
        $this->clearCacheByPattern('user_');
        $this->clearCacheByPattern('tenant_');
        $this->clearCacheByPattern('permissions_');
        $this->clearCacheByPattern('roles_');
    }

    /**
     * Warm cache for a user
     */
    public function warmUserCache(int $userId): void
    {
        $this->getUserById($userId);
        $this->getUserPermissions($userId);
        $this->getUserRoles($userId);
    }
}
