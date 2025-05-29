<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
// console logic to check if the application is running
    // This is a simple check to ensure the application is running
   \Illuminate\Support\Facades\Log::info('Message visible in terminal');

    return view('welcome');
});
