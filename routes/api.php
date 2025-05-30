<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


// added routes

Route::prefix('v1')->middleware(['tenant.access'])->group(function () {
    // pre auth urls
    Route::group(['prefix' => 'auth'], function () {

    });
});

