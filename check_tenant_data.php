<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

try {
    echo "Checking Database Data\n";
    echo "=====================\n\n";
    
    // Check tenants
    echo "1. Tenants:\n";
    $tenants = App\Models\Tenant::all(['id', 'name', 'domain']);
    if ($tenants->count() > 0) {
        foreach ($tenants as $tenant) {
            echo "   - ID: {$tenant->id}, Name: {$tenant->name}, Domain: {$tenant->domain}\n";
        }
    } else {
        echo "   No tenants found\n";
    }
    echo "\n";
    
    // Check users for first tenant
    if ($tenants->count() > 0) {
        $firstTenant = $tenants->first();
        echo "2. Users for tenant '{$firstTenant->name}' ({$firstTenant->id}):\n";
        $users = App\Models\User::where('tenant_id', $firstTenant->id)->get(['id', 'name', 'email', 'created_at']);
        if ($users->count() > 0) {
            foreach ($users as $user) {
                echo "   - {$user->name} ({$user->email}) - Created: {$user->created_at}\n";
            }
        } else {
            echo "   No users found for this tenant\n";
        }
        echo "\n";
        
        // Check courses for first tenant
        echo "3. Courses for tenant '{$firstTenant->name}':\n";
        $courses = App\Models\Course::where('tenant_id', $firstTenant->id)->get(['id', 'title', 'created_at']);
        if ($courses->count() > 0) {
            foreach ($courses as $course) {
                echo "   - {$course->title} (ID: {$course->id}) - Created: {$course->created_at}\n";
            }
        } else {
            echo "   No courses found for this tenant\n";
        }
        echo "\n";
        
        // Check enrollments
        echo "4. Course Enrollments for tenant '{$firstTenant->name}':\n";
        $enrollments = DB::table('course_user')
            ->join('courses', 'course_user.course_id', '=', 'courses.id')
            ->join('users', 'course_user.user_id', '=', 'users.id')
            ->where('courses.tenant_id', $firstTenant->id)
            ->select('users.name as user_name', 'courses.title as course_title', 'course_user.role', 'course_user.created_at')
            ->get();
            
        if ($enrollments->count() > 0) {
            foreach ($enrollments as $enrollment) {
                echo "   - {$enrollment->user_name} enrolled in '{$enrollment->course_title}' as {$enrollment->role}\n";
            }
        } else {
            echo "   No enrollments found for this tenant\n";
        }
        echo "\n";
        
        // Test analytics with real tenant ID
        echo "5. Testing Analytics with Real Tenant ID:\n";
        $analyticsService = app(App\Services\Analytics\AnalyticsService::class);
        $overview = $analyticsService->getAnalyticsOverview($firstTenant->id, '30d');
        echo "Analytics Overview:\n";
        echo json_encode($overview, JSON_PRETTY_PRINT) . "\n\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
