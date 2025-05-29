<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {

    echo 'Welcome to the Course Management System!';
    return view('welcome');
});
