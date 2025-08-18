<?php

// Test script for Course Content Editor API
require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\Http;

// Test configuration
$baseUrl = 'http://localhost:8000/api/v1';
$courseId = 1; // Update with actual course ID
$authToken = 'your-auth-token'; // Update with actual token

function testContentEditorAPI($baseUrl, $courseId, $authToken)
{
    $headers = [
        'Authorization' => "Bearer {$authToken}",
        'Accept' => 'application/json',
        'Content-Type' => 'application/json'
    ];

    echo "🚀 Testing Course Content Editor API\n";
    echo "=====================================\n\n";

    // Test 1: Get content types
    echo "1. Testing GET content types...\n";
    try {
        $response = Http::withHeaders($headers)
            ->get("{$baseUrl}/courses/{$courseId}/editor/content-types");

        echo "Status: " . $response->status() . "\n";
        echo "Response: " . $response->body() . "\n\n";
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n\n";
    }

    // Test 2: Get course content
    echo "2. Testing GET course content...\n";
    try {
        $response = Http::withHeaders($headers)
            ->get("{$baseUrl}/courses/{$courseId}/editor");

        echo "Status: " . $response->status() . "\n";
        echo "Response: " . $response->body() . "\n\n";
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n\n";
    }

    // Test 3: Create new content
    echo "3. Testing POST create content...\n";
    try {
        $contentData = [
            'title' => 'Test Lesson - ' . date('Y-m-d H:i:s'),
            'description' => 'This is a test lesson created via API',
            'type' => 'lesson',
            'content' => '<h1>Welcome to the Test Lesson</h1><p>This is some rich text content.</p>',
            'learning_objectives' => [
                'Understand the basics',
                'Apply the concepts',
                'Complete the exercises'
            ],
            'estimated_duration' => 30,
            'is_required' => true,
            'status' => 'draft'
        ];

        $response = Http::withHeaders($headers)
            ->post("{$baseUrl}/courses/{$courseId}/editor", $contentData);

        echo "Status: " . $response->status() . "\n";
        echo "Response: " . $response->body() . "\n\n";

        // Store content ID for further tests
        $responseData = $response->json();
        $contentId = $responseData['data']['id'] ?? null;

        if ($contentId) {
            // Test 4: Update content
            echo "4. Testing PUT update content...\n";
            try {
                $updateData = [
                    'title' => 'Updated Test Lesson - ' . date('Y-m-d H:i:s'),
                    'content' => '<h1>Updated Content</h1><p>This content has been updated.</p>',
                    'status' => 'published'
                ];

                $response = Http::withHeaders($headers)
                    ->put("{$baseUrl}/courses/{$courseId}/editor/{$contentId}", $updateData);

                echo "Status: " . $response->status() . "\n";
                echo "Response: " . $response->body() . "\n\n";
            } catch (Exception $e) {
                echo "Error: " . $e->getMessage() . "\n\n";
            }

            // Test 5: Get specific content
            echo "5. Testing GET specific content...\n";
            try {
                $response = Http::withHeaders($headers)
                    ->get("{$baseUrl}/courses/{$courseId}/editor/{$contentId}");

                echo "Status: " . $response->status() . "\n";
                echo "Response: " . $response->body() . "\n\n";
            } catch (Exception $e) {
                echo "Error: " . $e->getMessage() . "\n\n";
            }

            // Test 6: Duplicate content
            echo "6. Testing POST duplicate content...\n";
            try {
                $response = Http::withHeaders($headers)
                    ->post("{$baseUrl}/courses/{$courseId}/editor/{$contentId}/duplicate");

                echo "Status: " . $response->status() . "\n";
                echo "Response: " . $response->body() . "\n\n";

                $duplicateData = $response->json();
                $duplicateId = $duplicateData['data']['id'] ?? null;
            } catch (Exception $e) {
                echo "Error: " . $e->getMessage() . "\n\n";
            }

            // Test 7: Get content stats
            echo "7. Testing GET content statistics...\n";
            try {
                $response = Http::withHeaders($headers)
                    ->get("{$baseUrl}/courses/{$courseId}/editor/stats");

                echo "Status: " . $response->status() . "\n";
                echo "Response: " . $response->body() . "\n\n";
            } catch (Exception $e) {
                echo "Error: " . $e->getMessage() . "\n\n";
            }

            // Test 8: Delete duplicate content
            if (isset($duplicateId)) {
                echo "8. Testing DELETE content...\n";
                try {
                    $response = Http::withHeaders($headers)
                        ->delete("{$baseUrl}/courses/{$courseId}/editor/{$duplicateId}");

                    echo "Status: " . $response->status() . "\n";
                    echo "Response: " . $response->body() . "\n\n";
                } catch (Exception $e) {
                    echo "Error: " . $e->getMessage() . "\n\n";
                }
            }
        }
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n\n";
    }

    echo "✅ Content Editor API testing completed!\n";
}

// Only run if called directly
if (php_sapi_name() === 'cli') {
    if ($argc < 4) {
        echo "Usage: php test_content_editor_api.php <course_id> <auth_token> [base_url]\n";
        echo "Example: php test_content_editor_api.php 1 your-token http://localhost:8000/api/v1\n";
        exit(1);
    }

    $courseId = $argv[1];
    $authToken = $argv[2];
    $baseUrl = $argv[3] ?? 'http://localhost:8000/api/v1';

    testContentEditorAPI($baseUrl, $courseId, $authToken);
}
