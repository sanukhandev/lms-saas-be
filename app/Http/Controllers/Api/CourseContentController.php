<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CourseContent;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CourseContentController extends Controller
{
    public function index(Course $course): JsonResponse
    {
        $contents = $course->contents()
            ->orderBy('position')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $contents
        ]);
    }

    public function store(Request $request, Course $course): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|in:module,chapter',
            'description' => 'nullable|string',
            'duration_mins' => 'nullable|integer|min:1',
            'position' => 'nullable|integer|min:0',
            'parent_id' => 'nullable|integer|exists:course_contents,id',
        ]);

        $courseContent = $course->contents()->create([
            'tenant_id' => $course->tenant_id,
            'title' => $request->title,
            'type' => $request->type,
            'description' => $request->description,
            'duration_mins' => $request->duration_mins,
            'position' => $request->position ?? 0,
            'parent_id' => $request->parent_id,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Course content created successfully',
            'data' => $courseContent
        ], 201);
    }

    public function show(Course $course, CourseContent $content): JsonResponse
    {
        // Ensure the content belongs to the course
        if ($content->course_id !== $course->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Content not found in this course'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $content
        ]);
    }

    public function update(Request $request, Course $course, CourseContent $content): JsonResponse
    {
        // Ensure the content belongs to the course
        if ($content->course_id !== $course->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Content not found in this course'
            ], 404);
        }

        $request->validate([
            'title' => 'sometimes|string|max:255',
            'type' => 'sometimes|in:module,chapter',
            'description' => 'nullable|string',
            'duration_mins' => 'nullable|integer|min:1',
            'position' => 'nullable|integer|min:0',
            'parent_id' => 'nullable|integer|exists:course_contents,id',
        ]);

        $content->update($request->only([
            'title',
            'type',
            'description',
            'duration_mins',
            'position',
            'parent_id',
        ]));

        return response()->json([
            'status' => 'success',
            'message' => 'Course content updated successfully',
            'data' => $content->fresh()
        ]);
    }

    public function destroy(Course $course, CourseContent $content): JsonResponse
    {
        // Ensure the content belongs to the course
        if ($content->course_id !== $course->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Content not found in this course'
            ], 404);
        }

        $content->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Course content deleted successfully'
        ]);
    }

    public function tree(Course $course): JsonResponse
    {
        $contents = $course->contents()
            ->orderBy('position')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $this->buildTree($contents)
        ]);
    }

    public function reorder(Request $request, Course $course): JsonResponse
    {
        $request->validate([
            'content_ids' => 'required|array',
            'content_ids.*' => 'integer|exists:course_contents,id'
        ]);

        $contentIds = $request->content_ids;
        
        foreach ($contentIds as $index => $contentId) {
            CourseContent::where('id', $contentId)
                ->where('course_id', $course->id)
                ->update(['position' => $index]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Content reordered successfully'
        ]);
    }

    private function buildTree($contents)
    {
        // Simple tree structure - can be enhanced based on your needs
        return $contents->map(function ($content) {
            return [
                'id' => $content->id,
                'title' => $content->title,
                'type' => $content->type,
                'position' => $content->position,
                'duration_mins' => $content->duration_mins,
                'description' => $content->description,
                'parent_id' => $content->parent_id,
            ];
        });
    }
}
