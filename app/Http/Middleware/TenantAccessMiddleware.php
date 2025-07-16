<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class TenantAccessMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        // Super admin can access everything
        if ($user->role === 'super_admin') {
            return $next($request);
        }

        // Check if tenant is required for this user role
        if (in_array($user->role, ['admin', 'staff', 'tutor', 'student']) && !$user->tenant_id) {
            Log::warning('User without tenant trying to access tenant-protected resource', [
                'user_id' => $user->id,
                'role' => $user->role,
                'route' => $request->route()->getName()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'User must be associated with a tenant to access this resource'
            ], 403);
        }

        // Add tenant context to request
        if ($user->tenant_id) {
            $request->merge(['user_tenant_id' => $user->tenant_id]);
        }

        return $next($request);
    }
}
