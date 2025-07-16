<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseContent;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class CourseContentController extends Controller
{
    /**
     * Display a listing of course content
     */
    public function index(Request $request, Course $course): JsonResponse
    {
        try {
            // Check if course belongs to current tenant
            if ($course->tenant_id !== Auth::user()->tenant_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Course not found'
                ], 404);
            }

            $query = CourseContent::where('course_id', $course->id)
                ->where('tenant_id', Auth::user()->tenant_id);

            // Filter by parent (get root items or children of specific parent)
            if ($request->filled('parent_id')) {
                $query->where('parent_id', $request->parent_id);
            } else {
                $query->whereNull('parent_id');
            }

            // Filter by content type
            if ($request->filled('type')) {
                $query->where('type', $request->type);
            }

            // Include children if requested
            if ($request->boolean('include_children')) {
                $query->with(['children' => function ($q) {
                    $q->orderBy('position');
                }]);
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'position');
            $sortOrder = $request->get('sort_order', 'asc');
            $query->orderBy($sortBy, $sortOrder);

            $content = $query->get();

            return response()->json([
                'success' => true,
                'data' => $content,
                'message' => 'Course content retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error retrieving course content: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving course content'
            ], 500);
        }
    }

    /**
     * Store a newly created course content
     */
    public function store(Request $request, Course $course): JsonResponse
    {
        try {
            // Check if course belongs to current tenant
            if ($course->tenant_id !== Auth::user()->tenant_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Course not found'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'type' => 'required|in:module,lesson,assignment,quiz,video,document,link',
                'parent_id' => 'nullable|exists:course_contents,id',
                'position' => 'nullable|integer|min:1',
                'duration_mins' => 'nullable|integer|min:0'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Validate parent belongs to same course and tenant
            if ($request->parent_id) {
                $parent = CourseContent::where('id', $request->parent_id)
                    ->where('course_id', $course->id)
                    ->where('tenant_id', Auth::user()->tenant_id)
                    ->first();

                if (!$parent) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Parent content not found or does not belong to this course'
                    ], 404);
                }
            }

            // Auto-generate position if not provided
            $position = $request->position;
            if (!$position) {
                $maxPosition = CourseContent::where('course_id', $course->id)
                    ->where('parent_id', $request->parent_id)
                    ->max('position') ?? 0;
                $position = $maxPosition + 1;
            }

            $content = CourseContent::create([
                'course_id' => $course->id,
                'tenant_id' => Auth::user()->tenant_id,
                'parent_id' => $request->parent_id,
                'type' => $request->type,
                'title' => $request->title,
                'description' => $request->description,
                'position' => $position,
                'duration_mins' => $request->duration_mins ?? 0
            ]);

            $content->load(['parent', 'children']);

            return response()->json([
                'success' => true,
                'data' => $content,
                'message' => 'Course content created successfully'
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error creating course content: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error creating course content'
            ], 500);
        }
    }

    /**
     * Display the specified course content
     */
    public function show(Course $course, CourseContent $content): JsonResponse
    {
        try {
            // Check if course belongs to current tenant
            if ($course->tenant_id !== Auth::user()->tenant_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Course not found'
                ], 404);
            }

            // Check if content belongs to the course and tenant
            if ($content->course_id !== $course->id || $content->tenant_id !== Auth::user()->tenant_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Content not found'
                ], 404);
            }

            $content->load([
                'parent',
                'children' => function ($query) {
                    $query->orderBy('position');
                },
                'sessions'
            ]);

            return response()->json([
                'success' => true,
                'data' => $content,
                'message' => 'Course content retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error retrieving course content: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving course content'
            ], 500);
        }
    }

    /**
     * Update the specified course content
     */
    public function update(Request $request, Course $course, CourseContent $content): JsonResponse
    {
        try {
            // Check if course belongs to current tenant
            if ($course->tenant_id !== Auth::user()->tenant_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Course not found'
                ], 404);
            }

            // Check if content belongs to the course and tenant
            if ($content->course_id !== $course->id || $content->tenant_id !== Auth::user()->tenant_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Content not found'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'title' => 'sometimes|required|string|max:255',
                'description' => 'nullable|string',
                'type' => 'sometimes|required|in:module,lesson,assignment,quiz,video,document,link',
                'parent_id' => 'nullable|exists:course_contents,id',
                'position' => 'sometimes|integer|min:1',
                'duration_mins' => 'sometimes|integer|min:0'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Validate parent belongs to same course and tenant
            if ($request->has('parent_id') && $request->parent_id) {
                $parent = CourseContent::where('id', $request->parent_id)
                    ->where('course_id', $course->id)
                    ->where('tenant_id', Auth::user()->tenant_id)
                    ->first();

                if (!$parent) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Parent content not found or does not belong to this course'
                    ], 404);
                }

                // Prevent setting self as parent
                if ($request->parent_id == $content->id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Content cannot be its own parent'
                    ], 422);
                }
            }

            $content->update($request->only([
                'title',
                'description',
                'type',
                'parent_id',
                'position',
                'duration_mins'
            ]));

            $content->load(['parent', 'children']);

            return response()->json([
                'success' => true,
                'data' => $content,
                'message' => 'Course content updated successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating course content: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error updating course content'
            ], 500);
        }
    }

    /**
     * Remove the specified course content
     */
    public function destroy(Course $course, CourseContent $content): JsonResponse
    {
        try {
            // Check if course belongs to current tenant
            if ($course->tenant_id !== Auth::user()->tenant_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Course not found'
                ], 404);
            }

            // Check if content belongs to the course and tenant
            if ($content->course_id !== $course->id || $content->tenant_id !== Auth::user()->tenant_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Content not found'
                ], 404);
            }

            // Check if content has children
            if ($content->children()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete content with child items'
                ], 409);
            }

            $content->delete();

            return response()->json([
                'success' => true,
                'message' => 'Course content deleted successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error deleting course content: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error deleting course content'
            ], 500);
        }
    }

    /**
     * Reorder course content items
     */
    public function reorder(Request $request, Course $course): JsonResponse
    {
        try {
            // Check if course belongs to current tenant
            if ($course->tenant_id !== Auth::user()->tenant_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Course not found'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'items' => 'required|array',
                'items.*.id' => 'required|exists:course_contents,id',
                'items.*.position' => 'required|integer|min:1',
                'parent_id' => 'nullable|exists:course_contents,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            foreach ($request->items as $item) {
                CourseContent::where('id', $item['id'])
                    ->where('course_id', $course->id)
                    ->where('tenant_id', Auth::user()->tenant_id)
                    ->update([
                        'position' => $item['position'],
                        'parent_id' => $request->parent_id
                    ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Course content reordered successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error reordering course content: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error reordering course content'
            ], 500);
        }
    }

    /**
     * Get content tree structure for a course
     */
    public function tree(Course $course): JsonResponse
    {
        try {
            // Check if course belongs to current tenant
            if ($course->tenant_id !== Auth::user()->tenant_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Course not found'
                ], 404);
            }

            $content = CourseContent::with(['children' => function ($query) {
                $query->orderBy('position');
            }])
                ->where('course_id', $course->id)
                ->where('tenant_id', Auth::user()->tenant_id)
                ->whereNull('parent_id')
                ->orderBy('position')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $content,
                'message' => 'Course content tree retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error retrieving course content tree: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving course content tree'
            ], 500);
        }
    }
}
