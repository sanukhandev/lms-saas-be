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
        
        // Add other protected routes here
    });
});

