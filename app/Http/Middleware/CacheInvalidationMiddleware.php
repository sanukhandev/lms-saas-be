<?php

namespace App\Http\Middleware;

use App\Services\Cache\CacheManager;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CacheInvalidationMiddleware
{
    protected CacheManager $cacheManager;

    public function __construct(CacheManager $cacheManager)
    {
        $this->cacheManager = $cacheManager;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Only invalidate cache for successful write operations
        if ($response->isSuccessful() && in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            $this->invalidateCache($request);
        }

        return $response;
    }

    /**
     * Invalidate relevant cache based on the request
     */
    protected function invalidateCache(Request $request): void
    {
        try {
            $route = $request->route();
            if (!$route) {
                return;
            }

            $routeName = $route->getName();
            $routeParameters = $route->parameters();
            
            // Get tenant ID from various sources
            $tenantId = $this->getTenantId($request, $routeParameters);
            
            if (!$tenantId) {
                return;
            }

            // Invalidate cache based on route patterns
            $this->invalidateCacheByRoute($routeName, $routeParameters, $tenantId);
            
        } catch (\Exception $e) {
            Log::error("Cache invalidation failed: " . $e->getMessage());
        }
    }

    /**
     * Invalidate cache based on route name and parameters
     */
    protected function invalidateCacheByRoute(?string $routeName, array $parameters, int $tenantId): void
    {
        if (!$routeName) {
            return;
        }

        // Course-related routes
        if (str_contains($routeName, 'course')) {
            $courseId = $parameters['course'] ?? $parameters['courseId'] ?? null;
            if ($courseId) {
                $this->cacheManager->clearCourseRelatedCache($courseId, $tenantId);
            }
        }

        // User-related routes
        if (str_contains($routeName, 'user')) {
            $userId = $parameters['user'] ?? $parameters['userId'] ?? null;
            if ($userId) {
                $this->cacheManager->clearUserRelatedCache($userId, $tenantId);
            }
        }

        // Progress-related routes
        if (str_contains($routeName, 'progress')) {
            $userId = $parameters['user'] ?? $parameters['userId'] ?? null;
            $courseId = $parameters['course'] ?? $parameters['courseId'] ?? null;
            if ($userId && $courseId) {
                $this->cacheManager->clearProgressRelatedCache($userId, $courseId, $tenantId);
            }
        }

        // Purchase-related routes
        if (str_contains($routeName, 'purchase')) {
            $userId = $parameters['user'] ?? $parameters['userId'] ?? null;
            $courseId = $parameters['course'] ?? $parameters['courseId'] ?? null;
            if ($userId && $courseId) {
                $this->cacheManager->clearPurchaseRelatedCache($userId, $courseId, $tenantId);
            }
        }

        // Certificate-related routes
        if (str_contains($routeName, 'certificate')) {
            $userId = $parameters['user'] ?? $parameters['userId'] ?? null;
            $courseId = $parameters['course'] ?? $parameters['courseId'] ?? null;
            if ($userId && $courseId) {
                $this->cacheManager->clearCertificateRelatedCache($userId, $courseId, $tenantId);
            }
        }

        // Tenant settings routes
        if (str_contains($routeName, 'tenant') || str_contains($routeName, 'settings')) {
            $this->cacheManager->clearTenantCache($tenantId);
        }

        // Dashboard routes
        if (str_contains($routeName, 'dashboard')) {
            $this->cacheManager->clearTenantCache($tenantId);
        }
    }

    /**
     * Get tenant ID from request
     */
    protected function getTenantId(Request $request, array $routeParameters): ?int
    {
        // Try to get tenant ID from route parameters
        if (isset($routeParameters['tenant'])) {
            return (int) $routeParameters['tenant'];
        }

        if (isset($routeParameters['tenantId'])) {
            return (int) $routeParameters['tenantId'];
        }

        // Try to get tenant ID from request body
        if ($request->has('tenant_id')) {
            return (int) $request->input('tenant_id');
        }

        // Try to get tenant ID from authenticated user
        if ($request->user() && $request->user()->tenant_id) {
            return (int) $request->user()->tenant_id;
        }

        // Try to get tenant ID from subdomain or domain
        $host = $request->getHost();
        if ($host) {
            // This would require a service to resolve domain to tenant
            // For now, we'll return null and handle this in the TenantService
            return null;
        }

        return null;
    }
}
