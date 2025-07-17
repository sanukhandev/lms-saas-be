<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

try {
    echo "Student Progress Analysis\n";
    echo "========================\n\n";
    
    $tenantId = 1; // Demo Tenant
    
    // Check student progress records
    $totalProgress = App\Models\StudentProgress::where('tenant_id', $tenantId)->count();
    echo "Total Student Progress Records: {$totalProgress}\n";
    
    if ($totalProgress > 0) {
        $completedCourses = App\Models\StudentProgress::where('tenant_id', $tenantId)
            ->where('completion_percentage', 100)
            ->count();
        echo "Completed Courses: {$completedCourses}\n";
        
        $avgProgress = App\Models\StudentProgress::where('tenant_id', $tenantId)
            ->avg('completion_percentage');
        echo "Average Progress: " . round($avgProgress, 2) . "%\n\n";
        
        echo "Sample Progress Records:\n";
        $samples = App\Models\StudentProgress::where('tenant_id', $tenantId)
            ->limit(10)
            ->get(['user_id', 'course_id', 'completion_percentage', 'created_at']);
            
        foreach ($samples as $progress) {
            echo "  User ID: {$progress->user_id}, Course ID: {$progress->course_id}, Progress: {$progress->completion_percentage}%, Created: {$progress->created_at}\n";
        }
    } else {
        echo "No student progress records found. Let me create some sample data...\n";
        
        // Create sample student progress data
        $users = App\Models\User::where('tenant_id', $tenantId)->limit(20)->pluck('id');
        $courses = App\Models\Course::where('tenant_id', $tenantId)->limit(10)->pluck('id');
        
        if ($users->count() > 0 && $courses->count() > 0) {
            $progressData = [];
            foreach ($users as $userId) {
                foreach ($courses->random(rand(1, 5)) as $courseId) {
                    $progressData[] = [
                        'tenant_id' => $tenantId,
                        'user_id' => $userId,
                        'course_id' => $courseId,
                        'completion_percentage' => rand(0, 100),
                        'time_spent_minutes' => rand(30, 300),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
            
            // Insert sample data
            App\Models\StudentProgress::insert($progressData);
            
            echo "Created " . count($progressData) . " sample student progress records!\n";
            
            // Test analytics again
            echo "\nTesting Analytics After Creating Sample Data:\n";
            $analyticsService = app(App\Services\Analytics\AnalyticsService::class);
            $overview = $analyticsService->getAnalyticsOverview($tenantId, '30d');
            echo json_encode($overview, JSON_PRETTY_PRINT) . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
