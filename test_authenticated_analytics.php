<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

try {
    echo "Testing Analytics API with Authentication\n";
    echo "========================================\n\n";
    
    // Get a test user
    $testUser = App\Models\User::where('tenant_id', 1)->first();
    if (!$testUser) {
        echo "No test user found.\n";
        exit(1);
    }
    
    echo "Using test user: {$testUser->name} ({$testUser->email})\n\n";
    
    // Create a token for the user
    $token = $testUser->createToken('test-analytics')->plainTextToken;
    echo "Created API token: {$token}\n\n";
    
    // Test the analytics endpoint
    $url = 'http://localhost:8000/api/v1/analytics/overview?time_range=30d';
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'Content-Type: application/json',
        'Authorization: Bearer ' . $token
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "HTTP Status Code: {$httpCode}\n";
    echo "API Response:\n";
    
    if ($response) {
        $data = json_decode($response, true);
        echo json_encode($data, JSON_PRETTY_PRINT) . "\n";
    } else {
        echo "No response received\n";
    }
    
    // Clean up the token
    $testUser->tokens()->delete();
    echo "\nAPI token cleaned up.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
