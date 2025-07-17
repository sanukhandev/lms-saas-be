<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

try {
    
    echo "Testing Analytics API Endpoints\n";
    echo "================================\n\n";
    
    // Test analytics overview
    echo "1. Testing Analytics Overview:\n";
    $controller = app(App\Http\Controllers\Api\AnalyticsController::class);
    
    // Create a mock request with time_range parameter
    $request = new \Illuminate\Http\Request(['time_range' => '30d']);
    
    try {
        $response = $controller->overview($request);
        $data = $response->getData(true);
        echo "✓ Analytics Overview Response:\n";
        echo json_encode($data, JSON_PRETTY_PRINT) . "\n\n";
    } catch (Exception $e) {
        echo "✗ Error: " . $e->getMessage() . "\n\n";
    }
    
    // Test engagement metrics
    echo "2. Testing Engagement Metrics:\n";
    try {
        $response = $controller->getEngagementMetrics($request);
        $data = $response->getData(true);
        echo "✓ Engagement Metrics Response:\n";
        echo json_encode($data, JSON_PRETTY_PRINT) . "\n\n";
    } catch (Exception $e) {
        echo "✗ Error: " . $e->getMessage() . "\n\n";
    }
    
    // Test performance metrics
    echo "3. Testing Performance Metrics:\n";
    try {
        $response = $controller->getPerformanceMetrics($request);
        $data = $response->getData(true);
        echo "✓ Performance Metrics Response:\n";
        echo json_encode($data, JSON_PRETTY_PRINT) . "\n\n";
    } catch (Exception $e) {
        echo "✗ Error: " . $e->getMessage() . "\n\n";
    }
    
    echo "Analytics API testing completed!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
