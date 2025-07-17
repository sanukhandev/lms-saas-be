<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

try {
    echo "Testing Analytics Service Directly\n";
    echo "===================================\n\n";
    
    // Test the AnalyticsService directly without authentication
    $analyticsService = app(App\Services\Analytics\AnalyticsService::class);
    
    // Use a dummy tenant ID for testing
    $tenantId = '123e4567-e89b-12d3-a456-426614174000';
    $timeRange = '30d';
    
    echo "1. Testing Analytics Overview Service:\n";
    try {
        $overview = $analyticsService->getAnalyticsOverview($tenantId, $timeRange);
        echo "✓ Analytics Overview Service Response:\n";
        echo json_encode($overview, JSON_PRETTY_PRINT) . "\n\n";
    } catch (Exception $e) {
        echo "✗ Error: " . $e->getMessage() . "\n\n";
    }
    
    echo "2. Testing Engagement Metrics Service:\n";
    try {
        $engagement = $analyticsService->getEngagementMetrics($tenantId, $timeRange);
        echo "✓ Engagement Metrics Service Response:\n";
        echo json_encode($engagement, JSON_PRETTY_PRINT) . "\n\n";
    } catch (Exception $e) {
        echo "✗ Error: " . $e->getMessage() . "\n\n";
    }
    
    echo "3. Testing Performance Metrics Service:\n";
    try {
        $performance = $analyticsService->getPerformanceMetrics($tenantId, $timeRange);
        echo "✓ Performance Metrics Service Response:\n";
        echo json_encode($performance, JSON_PRETTY_PRINT) . "\n\n";
    } catch (Exception $e) {
        echo "✗ Error: " . $e->getMessage() . "\n\n";
    }
    
    echo "Analytics Service testing completed!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
