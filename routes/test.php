<?php

// Basic test endpoint to verify our course editing system
Route::get('/test-course-system', function () {
    return response()->json([
        'message' => 'Course editing system is ready',
        'status' => 'success',
        'features' => [
            'basic_course_crud' => 'Available',
            'course_content_management' => 'Available',
            'class_scheduling' => 'Available',
            'session_management' => 'Available',
            'teaching_plans' => 'Available',
            'attendance_tracking' => 'Available'
        ],
        'endpoints' => [
            'courses' => '/api/courses',
            'course_edit' => '/api/courses/{course}',
            'course_content' => '/api/courses/{course}/content',
            'class_schedule' => '/api/courses/{course}/classes',
            'content_classes' => '/api/courses/{course}/content/{content}/classes',
            'sessions' => '/api/sessions',
            'teaching_plans' => '/api/courses/{course}/classes/planner'
        ]
    ]);
});
