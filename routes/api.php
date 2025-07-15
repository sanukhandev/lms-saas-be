<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Test route for CORS
Route::get('/test', function () {
    return response()->json(['message' => 'CORS test successful', 'timestamp' => now()]);
});

// added routes

Route::prefix('v1')->group(function () {
    // Public tenant routes (no authentication required)
    Route::get('tenants/domain/{domain}', [App\Http\Controllers\Api\TenantController::class, 'getByDomain']);
    
    // Authentication routes (public) - no tenant middleware
    Route::group(['prefix' => 'auth'], function () {
        Route::post('register', [App\Http\Controllers\Api\AuthController::class, 'register']);
        Route::post('login', [App\Http\Controllers\Api\AuthController::class, 'login']);
        Route::post('logout', [App\Http\Controllers\Api\AuthController::class, 'logout'])->middleware('auth:sanctum');
        Route::post('refresh', [App\Http\Controllers\Api\AuthController::class, 'refresh'])->middleware('auth:sanctum');
        Route::post('change-password', [App\Http\Controllers\Api\AuthController::class, 'changePassword'])->middleware('auth:sanctum');
    });

    // Protected routes with tenant middleware
    Route::middleware(['auth:sanctum', 'tenant.access'])->group(function () {
        Route::get('user', [App\Http\Controllers\Api\AuthController::class, 'user']);
        
        // Tenant management routes
        Route::get('tenants', [App\Http\Controllers\Api\TenantController::class, 'index']); // Super admin only
        Route::get('tenants/current', [App\Http\Controllers\Api\TenantController::class, 'current']);
        Route::put('tenants/{domain}/settings', [App\Http\Controllers\Api\TenantController::class, 'updateSettings']);
        
        // Add other protected routes here
    });
});

