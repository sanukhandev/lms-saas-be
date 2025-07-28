<?php

// Simple test script to check the course structure with tree implementation
require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Test course ID - Update this with a valid course ID from your database
$courseId = '1'; 

// Create a test request to get course structure
$request = Request::create("/api/v1/courses/{$courseId}/structure", 'GET', [], [], [], [
    'HTTP_AUTHORIZATION' => 'Bearer 2|hmi2QveT1j2dbPTuexCZz0rLvdU71tgyjDDdSiLA3df96ce7',
    'HTTP_ACCEPT' => 'application/json',
    'HTTP_X_TENANT_DOMAIN' => 'demo',
    'HTTP_X_TENANT_ID' => '1'
]);

try {
    echo "Testing course structure endpoint...\n";
    $response = $kernel->handle($request);
    echo "Status: " . $response->getStatusCode() . "\n";
    echo "Content: " . $response->getContent() . "\n\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

// Create a test request to check the Course model's tree relationships
$request = Request::create('/api/test/course-tree', 'GET', [], [], [], [
    'HTTP_AUTHORIZATION' => 'Bearer 2|hmi2QveT1j2dbPTuexCZz0rLvdU71tgyjDDdSiLA3df96ce7',
    'HTTP_ACCEPT' => 'application/json',
    'HTTP_X_TENANT_DOMAIN' => 'demo',
    'HTTP_X_TENANT_ID' => '1'
]);

try {
    echo "Testing Course model tree relationships...\n";
    // Override the request in the container
    $app->instance('request', $request);
    
    // Define a test route for the request
    $app['router']->get('/api/test/course-tree', function() use ($courseId) {
        // Get the course
        $course = \App\Models\Course::find($courseId);
        if (!$course) {
            return response()->json([
                'success' => false,
                'message' => 'Course not found',
            ], 404);
        }

        // Test the tree relationships
        $modules = $course->modules()->get();
        $modulesData = [];
        
        foreach ($modules as $module) {
            $chapters = $module->children()->where('content_type', 'chapter')->get();
            $chaptersData = [];
            
            foreach ($chapters as $chapter) {
                $chaptersData[] = [
                    'id' => $chapter->id,
                    'title' => $chapter->title,
                    'content_type' => $chapter->content_type,
                    'position' => $chapter->position,
                ];
            }
            
            $modulesData[] = [
                'id' => $module->id,
                'title' => $module->title,
                'content_type' => $module->content_type,
                'position' => $module->position,
                'chapters' => $chaptersData,
            ];
        }
        
        return response()->json([
            'success' => true,
            'course' => [
                'id' => $course->id,
                'title' => $course->title,
                'content_type' => $course->content_type,
            ],
            'modules' => $modulesData,
            'structure' => $course->getTree(),
        ]);
    });
    
    $response = $kernel->handle($request);
    echo "Status: " . $response->getStatusCode() . "\n";
    echo "Content: " . $response->getContent() . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

$kernel->terminate($request, $response);
