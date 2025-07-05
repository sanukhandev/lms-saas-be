<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureTenantAccess
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user && $user->role === 'super_admin') {
            return $next($request);
        }

        $tenantId = $user->tenant_id ?? $request->header('X-Tenant-ID');

        if (!$tenantId || ($user && $user->tenant_id != $tenantId)) {
            return response()->json(['error' => 'Unauthorized tenant access'], 403);
        }

        return $next($request);
    }
}

