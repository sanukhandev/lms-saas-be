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

        // Theme routes (at root level)
        Route::prefix('theme')->group(function () {
            Route::get('color-palettes', [App\Http\Controllers\Api\TenantSettingsController::class, 'getColorPalettes']);
            Route::get('presets', [App\Http\Controllers\Api\TenantSettingsController::class, 'getPresetThemes']);
        });

        // Tenant management routes
        Route::get('tenants', [App\Http\Controllers\Api\TenantController::class, 'index']); // Super admin only
        Route::get('tenants/current', [App\Http\Controllers\Api\TenantController::class, 'current']);
        Route::put('tenants/{domain}/settings', [App\Http\Controllers\Api\TenantController::class, 'updateSettings']);

        // Tenant Settings Routes
        Route::prefix('tenant/settings')->group(function () {
            // General settings
            Route::get('general', [App\Http\Controllers\Api\TenantSettingsController::class, 'getGeneralSettings']);
            Route::put('general', [App\Http\Controllers\Api\TenantSettingsController::class, 'updateGeneralSettings']);

            // Branding settings
            Route::get('branding', [App\Http\Controllers\Api\TenantSettingsController::class, 'getBrandingSettings']);
            Route::put('branding', [App\Http\Controllers\Api\TenantSettingsController::class, 'updateBrandingSettings']);

            // Features settings
            Route::get('features', [App\Http\Controllers\Api\TenantSettingsController::class, 'getFeaturesSettings']);
            Route::put('features', [App\Http\Controllers\Api\TenantSettingsController::class, 'updateFeaturesSettings']);

            // Security settings
            Route::get('security', [App\Http\Controllers\Api\TenantSettingsController::class, 'getSecuritySettings']);
            Route::put('security', [App\Http\Controllers\Api\TenantSettingsController::class, 'updateSecuritySettings']);        // Theme settings
            Route::get('theme', [App\Http\Controllers\Api\TenantSettingsController::class, 'getThemeSettings']);
            Route::put('theme', [App\Http\Controllers\Api\TenantSettingsController::class, 'updateThemeSettings']);
            Route::get('theme/color-palettes', [App\Http\Controllers\Api\TenantSettingsController::class, 'getColorPalettes']);
        });

        // Dashboard routes
        Route::get('dashboard', [App\Http\Controllers\Api\DashboardController::class, 'index']);
        Route::get('dashboard/overview', [App\Http\Controllers\Api\DashboardController::class, 'overview']);
        Route::get('dashboard/chart-data', [App\Http\Controllers\Api\DashboardController::class, 'getChartData']);
        Route::get('dashboard/stats', [App\Http\Controllers\Api\DashboardController::class, 'getStats']);
        Route::get('dashboard/activity', [App\Http\Controllers\Api\DashboardController::class, 'getActivity']);
        Route::get('dashboard/courses', [App\Http\Controllers\Api\DashboardController::class, 'getCourses']);
        Route::get('dashboard/users', [App\Http\Controllers\Api\DashboardController::class, 'getUsers']);
        Route::get('dashboard/users/stats', [App\Http\Controllers\Api\DashboardController::class, 'getUserStats']);
        Route::get('dashboard/users/activity', [App\Http\Controllers\Api\DashboardController::class, 'getUserActivity']);
        Route::get('dashboard/payments', [App\Http\Controllers\Api\DashboardController::class, 'getPayments']);

        // Analytics routes
        Route::prefix('analytics')->group(function () {
            Route::get('overview', [App\Http\Controllers\Api\AnalyticsController::class, 'overview']);
            Route::get('engagement', [App\Http\Controllers\Api\AnalyticsController::class, 'getEngagementMetrics']);
            Route::get('performance', [App\Http\Controllers\Api\AnalyticsController::class, 'getPerformanceMetrics']);
            Route::get('trends', [App\Http\Controllers\Api\AnalyticsController::class, 'getTrendAnalysis']);
            Route::get('user-behavior', [App\Http\Controllers\Api\AnalyticsController::class, 'getUserBehaviorAnalytics']);
            Route::get('course-analytics', [App\Http\Controllers\Api\AnalyticsController::class, 'getCourseAnalytics']);
            Route::get('revenue-analytics', [App\Http\Controllers\Api\AnalyticsController::class, 'getRevenueAnalytics']);
            Route::get('retention', [App\Http\Controllers\Api\AnalyticsController::class, 'getRetentionMetrics']);
        });

        // Course Management Routes
        Route::prefix('courses')->group(function () {
            Route::get('/', [App\Http\Controllers\Api\CourseController::class, 'index']);
            Route::post('/', [App\Http\Controllers\Api\CourseController::class, 'store']);
            Route::get('/statistics', [App\Http\Controllers\Api\CourseController::class, 'statistics']);
            Route::get('/{course}', [App\Http\Controllers\Api\CourseController::class, 'show']);
            Route::put('/{course}', [App\Http\Controllers\Api\CourseController::class, 'update']);
            Route::delete('/{course}', [App\Http\Controllers\Api\CourseController::class, 'destroy']);
            Route::post('/{course}/enroll', [App\Http\Controllers\Api\CourseController::class, 'enrollStudents']);
            Route::get('/{course}/students', [App\Http\Controllers\Api\CourseController::class, 'getEnrolledStudents']);

            // Course Content Routes
            Route::get('/{course}/content', [App\Http\Controllers\Api\CourseContentController::class, 'index']);
            Route::post('/{course}/content', [App\Http\Controllers\Api\CourseContentController::class, 'store']);
            Route::get('/{course}/content/tree', [App\Http\Controllers\Api\CourseContentController::class, 'tree']);
            Route::post('/{course}/content/reorder', [App\Http\Controllers\Api\CourseContentController::class, 'reorder']);
            Route::get('/{course}/content/{content}', [App\Http\Controllers\Api\CourseContentController::class, 'show']);
            Route::put('/{course}/content/{content}', [App\Http\Controllers\Api\CourseContentController::class, 'update']);
            Route::delete('/{course}/content/{content}', [App\Http\Controllers\Api\CourseContentController::class, 'destroy']);
        });

        // Category Management Routes
        Route::prefix('categories')->group(function () {
            Route::get('/', [App\Http\Controllers\Api\CategoryController::class, 'index']);
            Route::post('/', [App\Http\Controllers\Api\CategoryController::class, 'store']);
            Route::get('/tree', [App\Http\Controllers\Api\CategoryController::class, 'tree']);
            Route::get('/dropdown', [App\Http\Controllers\Api\CategoryController::class, 'dropdown']);
            Route::get('/statistics', [App\Http\Controllers\Api\CategoryController::class, 'statistics']);
            Route::get('/{category}', [App\Http\Controllers\Api\CategoryController::class, 'show']);
            Route::put('/{category}', [App\Http\Controllers\Api\CategoryController::class, 'update']);
            Route::delete('/{category}', [App\Http\Controllers\Api\CategoryController::class, 'destroy']);
        });

        // Enrollment Management Routes
        Route::prefix('enrollments')->group(function () {
            Route::get('/', [App\Http\Controllers\Api\EnrollmentController::class, 'index']);
            Route::post('/', [App\Http\Controllers\Api\EnrollmentController::class, 'store']);
            Route::post('/bulk', [App\Http\Controllers\Api\EnrollmentController::class, 'bulkEnroll']);
            Route::delete('/', [App\Http\Controllers\Api\EnrollmentController::class, 'destroy']);
            Route::get('/statistics', [App\Http\Controllers\Api\EnrollmentController::class, 'statistics']);
            Route::get('/student/{student}/history', [App\Http\Controllers\Api\EnrollmentController::class, 'studentHistory']);
        });

        // User Management Routes
        Route::prefix('users')->group(function () {
            Route::get('/', [App\Http\Controllers\Api\UserController::class, 'index']);
            Route::post('/', [App\Http\Controllers\Api\UserController::class, 'store']);
            Route::get('/statistics', [App\Http\Controllers\Api\UserController::class, 'statistics']);
            Route::post('/bulk-import', [App\Http\Controllers\Api\UserController::class, 'bulkImport']);
            Route::get('/role/{role}', [App\Http\Controllers\Api\UserController::class, 'getByRole']);
            Route::get('/{user}', [App\Http\Controllers\Api\UserController::class, 'show']);
            Route::put('/{user}', [App\Http\Controllers\Api\UserController::class, 'update']);
            Route::delete('/{user}', [App\Http\Controllers\Api\UserController::class, 'destroy']);
        });

        // Theme Management Routes
        Route::prefix('theme')->group(function () {
            Route::get('/color-palettes', [App\Http\Controllers\Api\TenantSettingsController::class, 'getColorPalettes']);
            Route::get('/presets', [App\Http\Controllers\Api\TenantSettingsController::class, 'getPresetThemes']);
        });

        // Cache Management Routes (Admin only)
        Route::prefix('cache')->group(function () {
            Route::get('/stats', [App\Http\Controllers\Api\CacheController::class, 'stats']);
            Route::post('/clear/tenant', [App\Http\Controllers\Api\CacheController::class, 'clearTenantCache']);
            Route::post('/warmup/tenant', [App\Http\Controllers\Api\CacheController::class, 'warmUpTenantCache']);
            Route::get('/keys', [App\Http\Controllers\Api\CacheController::class, 'getKeys']);
            Route::get('/value', [App\Http\Controllers\Api\CacheController::class, 'getValue']);
            Route::post('/value', [App\Http\Controllers\Api\CacheController::class, 'setValue']);
            Route::delete('/key', [App\Http\Controllers\Api\CacheController::class, 'deleteKey']);
            Route::post('/flush', [App\Http\Controllers\Api\CacheController::class, 'flushAll']);
            Route::post('/clear-expired', [App\Http\Controllers\Api\CacheController::class, 'clearExpired']);
        });

        // Add other protected routes here
    });
});
