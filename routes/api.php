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

            // Course Content Management (Modules & Chapters)
            Route::prefix('/{course}/content')->group(function () {
                Route::get('/', [App\Http\Controllers\Api\CourseContentController::class, 'index']);
                Route::post('/', [App\Http\Controllers\Api\CourseContentController::class, 'store']);
                Route::get('/tree', [App\Http\Controllers\Api\CourseContentController::class, 'tree']);
                Route::post('/reorder', [App\Http\Controllers\Api\CourseContentController::class, 'reorder']);
                Route::get('/{content}', [App\Http\Controllers\Api\CourseContentController::class, 'show']);
                Route::put('/{content}', [App\Http\Controllers\Api\CourseContentController::class, 'update']);
                Route::delete('/{content}', [App\Http\Controllers\Api\CourseContentController::class, 'destroy']);
                
                // Class Scheduling for specific content
                Route::prefix('/{content}/classes')->group(function () {
                    Route::get('/', [App\Http\Controllers\Api\ClassScheduleController::class, 'getContentClasses']);
                    Route::post('/', [App\Http\Controllers\Api\ClassScheduleController::class, 'scheduleClass']);
                    Route::put('/{session}', [App\Http\Controllers\Api\ClassScheduleController::class, 'updateSchedule']);
                    Route::delete('/{session}', [App\Http\Controllers\Api\ClassScheduleController::class, 'cancelClass']);
                });
            });

            // Course-Level Class Scheduling & Planning
            Route::prefix('/{course}/classes')->group(function () {
                Route::get('/', [App\Http\Controllers\Api\ClassScheduleController::class, 'getCourseClasses']);
                Route::post('/', [App\Http\Controllers\Api\ClassScheduleController::class, 'scheduleClass']);
                Route::get('/planner', [App\Http\Controllers\Api\ClassScheduleController::class, 'getClassPlanner']);
                Route::post('/planner', [App\Http\Controllers\Api\ClassScheduleController::class, 'createTeachingPlan']);
                Route::put('/planner/{plan}', [App\Http\Controllers\Api\ClassScheduleController::class, 'updateTeachingPlan']);
                Route::delete('/planner/{plan}', [App\Http\Controllers\Api\ClassScheduleController::class, 'deleteTeachingPlan']);
                Route::post('/bulk-schedule', [App\Http\Controllers\Api\ClassScheduleController::class, 'bulkScheduleClasses']);
            });

            // Session Management
            Route::prefix('/{course}/sessions')->group(function () {
                Route::get('/', [App\Http\Controllers\Api\SessionController::class, 'index']);
                Route::get('/{session}', [App\Http\Controllers\Api\SessionController::class, 'show']);
                Route::put('/{session}', [App\Http\Controllers\Api\SessionController::class, 'update']);
                Route::post('/{session}/start', [App\Http\Controllers\Api\SessionController::class, 'startSession']);
                Route::post('/{session}/end', [App\Http\Controllers\Api\SessionController::class, 'endSession']);
                Route::post('/{session}/attendance', [App\Http\Controllers\Api\SessionController::class, 'markAttendance']);
            });
        });

        // Course Builder Routes (Legacy - keeping for compatibility)
        Route::prefix('course-builder')->group(function () {
            Route::get('/{courseId}/structure', [App\Http\Controllers\Api\CourseBuilderController::class, 'getCourseStructure']);
            Route::post('/{courseId}/modules', [App\Http\Controllers\Api\CourseBuilderController::class, 'createModule']);
            Route::put('/{courseId}/modules/{moduleId}', [App\Http\Controllers\Api\CourseBuilderController::class, 'updateModule']);
            Route::delete('/{courseId}/modules/{moduleId}', [App\Http\Controllers\Api\CourseBuilderController::class, 'deleteModule']);
            Route::post('/{courseId}/modules/{moduleId}/chapters', [App\Http\Controllers\Api\CourseBuilderController::class, 'createChapter']);
            Route::put('/{courseId}/modules/{moduleId}/chapters/{chapterId}', [App\Http\Controllers\Api\CourseBuilderController::class, 'updateChapter']);
            Route::delete('/{courseId}/modules/{moduleId}/chapters/{chapterId}', [App\Http\Controllers\Api\CourseBuilderController::class, 'deleteChapter']);
            Route::post('/{courseId}/reorder', [App\Http\Controllers\Api\CourseBuilderController::class, 'reorderContent']);
            Route::get('/{courseId}/pricing', [App\Http\Controllers\Api\CourseBuilderController::class, 'getCoursePricing']);
            Route::put('/{courseId}/pricing', [App\Http\Controllers\Api\CourseBuilderController::class, 'updateCoursePricing']);
            Route::get('/access-models', [App\Http\Controllers\Api\CourseBuilderController::class, 'getSupportedAccessModels']);
            Route::post('/{courseId}/publish', [App\Http\Controllers\Api\CourseBuilderController::class, 'publishCourse']);
            Route::post('/{courseId}/unpublish', [App\Http\Controllers\Api\CourseBuilderController::class, 'unpublishCourse']);
        });

        // Course Builder Routes
        Route::prefix('course-builder')->group(function () {
            Route::get('/{course}/structure', [App\Http\Controllers\Api\CourseBuilderController::class, 'getCourseStructure']);
            
            // Module management
            Route::post('/{course}/modules', [App\Http\Controllers\Api\CourseBuilderController::class, 'createModule']);
            Route::put('/modules/{module}', [App\Http\Controllers\Api\CourseBuilderController::class, 'updateModule']);
            Route::delete('/modules/{module}', [App\Http\Controllers\Api\CourseBuilderController::class, 'deleteModule']);
            
            // Chapter management
            Route::post('/modules/{module}/chapters', [App\Http\Controllers\Api\CourseBuilderController::class, 'createChapter']);
            Route::put('/chapters/{chapter}', [App\Http\Controllers\Api\CourseBuilderController::class, 'updateChapter']);
            Route::delete('/chapters/{chapter}', [App\Http\Controllers\Api\CourseBuilderController::class, 'deleteChapter']);
            
            // Content reordering
            Route::post('/{course}/reorder', [App\Http\Controllers\Api\CourseBuilderController::class, 'reorderContent']);
            
            // Publishing
            Route::post('/{course}/publish', [App\Http\Controllers\Api\CourseBuilderController::class, 'publishCourse']);
            Route::post('/{course}/unpublish', [App\Http\Controllers\Api\CourseBuilderController::class, 'unpublishCourse']);
            
            // Pricing management
            Route::get('/{course}/pricing', [App\Http\Controllers\Api\CourseBuilderController::class, 'getCoursePricing']);
            Route::put('/{course}/pricing', [App\Http\Controllers\Api\CourseBuilderController::class, 'updateCoursePricing']);
            Route::get('/{course}/pricing/local', [App\Http\Controllers\Api\CourseBuilderController::class, 'getLocalPricing']);
            Route::post('/pricing/bulk', [App\Http\Controllers\Api\CourseBuilderController::class, 'getBulkPricing']);
            Route::post('/{course}/pricing/validate', [App\Http\Controllers\Api\CourseBuilderController::class, 'validatePricing']);
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
