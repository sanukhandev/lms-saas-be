<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Cache\CacheManager;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CacheController extends Controller
{
    protected CacheManager $cacheManager;

    public function __construct(CacheManager $cacheManager)
    {
        $this->cacheManager = $cacheManager;
    }

    /**
     * Get cache statistics
     */
    public function stats(): JsonResponse
    {
        $stats = $this->cacheManager->getCacheStats();
        
        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Clear cache for a specific tenant
     */
    public function clearTenantCache(Request $request): JsonResponse
    {
        $request->validate([
            'tenant_id' => 'required|integer',
        ]);

        $tenantId = $request->input('tenant_id');
        
        // Use more efficient tag-based cache clearing
        $tags = [
            "tenant:{$tenantId}",
            'dashboard',
            'analytics',
            'courses',
            'users',
            'sessions'
        ];
        
        foreach ($tags as $tag) {
            \Cache::tags($tag)->flush();
        }
        
        $this->cacheManager->clearTenantCache($tenantId);
        
        return response()->json([
            'success' => true,
            'message' => "Cache cleared for tenant {$tenantId}",
        ]);
    }

    /**
     * Warm up cache for a specific tenant
     */
    public function warmUpTenantCache(Request $request): JsonResponse
    {
        $request->validate([
            'tenant_id' => 'required|integer',
        ]);

        $tenantId = $request->input('tenant_id');
        
        $this->cacheManager->warmUpTenantCache($tenantId);
        
        return response()->json([
            'success' => true,
            'message' => "Cache warmed up for tenant {$tenantId}",
        ]);
    }

    /**
     * Get cache keys by pattern
     */
    public function getKeys(Request $request): JsonResponse
    {
        $pattern = $request->input('pattern', '*');
        $keys = $this->cacheManager->getCacheKeysByPattern($pattern);
        
        return response()->json([
            'success' => true,
            'data' => [
                'pattern' => $pattern,
                'count' => count($keys),
                'keys' => array_slice($keys, 0, 100), // Limit to 100 keys for response size
            ],
        ]);
    }

    /**
     * Get cache value by key
     */
    public function getValue(Request $request): JsonResponse
    {
        $request->validate([
            'key' => 'required|string',
        ]);

        $key = $request->input('key');
        $value = $this->cacheManager->getCacheValue($key);
        
        return response()->json([
            'success' => true,
            'data' => [
                'key' => $key,
                'value' => $value,
                'exists' => $value !== null,
            ],
        ]);
    }

    /**
     * Set cache value
     */
    public function setValue(Request $request): JsonResponse
    {
        $request->validate([
            'key' => 'required|string',
            'value' => 'required',
            'ttl' => 'nullable|integer|min:1',
        ]);

        $key = $request->input('key');
        $value = $request->input('value');
        $ttl = $request->input('ttl', 3600);
        
        $result = $this->cacheManager->setCacheValue($key, $value, $ttl);
        
        return response()->json([
            'success' => $result,
            'message' => $result ? 'Cache value set successfully' : 'Failed to set cache value',
        ]);
    }

    /**
     * Delete cache key
     */
    public function deleteKey(Request $request): JsonResponse
    {
        $request->validate([
            'key' => 'required|string',
        ]);

        $key = $request->input('key');
        $result = $this->cacheManager->deleteCacheKey($key);
        
        return response()->json([
            'success' => $result,
            'message' => $result ? 'Cache key deleted successfully' : 'Failed to delete cache key',
        ]);
    }

    /**
     * Flush all cache
     */
    public function flushAll(): JsonResponse
    {
        $this->cacheManager->flushAll();
        
        return response()->json([
            'success' => true,
            'message' => 'All cache flushed successfully',
        ]);
    }

    /**
     * Clear expired cache entries
     */
    public function clearExpired(): JsonResponse
    {
        $this->cacheManager->clearExpiredCache();
        
        return response()->json([
            'success' => true,
            'message' => 'Expired cache entries cleared',
        ]);
    }
}
