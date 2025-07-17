<?php

// Simple test script to check API endpoints
require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Create a test request
$request = Request::create('/api/v1/dashboard/users/activity', 'GET', [], [], [], [
    'HTTP_AUTHORIZATION' => 'Bearer 2|hmi2QveT1j2dbPTuexCZz0rLvdU71tgyjDDdSiLA3df96ce7',
    'HTTP_ACCEPT' => 'application/json',
    'HTTP_X_TENANT_DOMAIN' => 'demo',
    'HTTP_X_TENANT_ID' => '1'
]);

try {
    $response = $kernel->handle($request);
    echo "Status: " . $response->getStatusCode() . "\n";
    echo "Content: " . $response->getContent() . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

$kernel->terminate($request, $response);
