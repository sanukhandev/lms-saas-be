<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Category;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CourseController extends Controller
{
    /**
     * Display a listing of courses with filtering and pagination
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Course::with(['category', 'users'])
                ->where('tenant_id', Auth::user()->tenant_id);

            // Apply filters
            if ($request->filled('category_id')) {
                $query->where('category_id', $request->category_id);
            }

            if ($request->filled('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            if ($request->filled('search')) {
                $query->where(function ($q) use ($request) {
                    $q->where('title', 'like', '%' . $request->search . '%')
                      ->orWhere('description', 'like', '%' . $request->search . '%');
                });
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->get('per_page', 15);
            $courses = $query->paginate($perPage);

            // Transform the data to include additional information
            $courses->getCollection()->transform(function ($course) {
                $course->student_count = $course->users()->wherePivot('role', 'student')->count();
                $course->instructor_count = $course->users()->wherePivot('role', 'instructor')->count();
                return $course;
            });

            return response()->json([
                'success' => true,
                'data' => $courses,
                'message' => 'Courses retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error retrieving courses: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving courses'
            ], 500);
        }
    }

    /**
     * Store a newly created course
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'description' => 'required|string',
                'category_id' => 'required|exists:categories,id',
                'schedule_level' => 'required|in:beginner,intermediate,advanced',
                'is_active' => 'boolean',
                'instructors' => 'array',
                'instructors.*' => 'exists:users,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            $course = Course::create([
                'tenant_id' => Auth::user()->tenant_id,
                'title' => $request->title,
                'description' => $request->description,
                'category_id' => $request->category_id,
                'schedule_level' => $request->schedule_level,
                'is_active' => $request->get('is_active', true)
            ]);

            // Attach instructors if provided
            if ($request->has('instructors')) {
                $instructors = collect($request->instructors)->mapWithKeys(function ($userId) {
                    return [$userId => ['role' => 'instructor']];
                });
                $course->users()->attach($instructors);
            }

            DB::commit();

            $course->load(['category', 'users']);

            return response()->json([
                'success' => true,
                'data' => $course,
                'message' => 'Course created successfully'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating course: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error creating course'
            ], 500);
        }
    }

    /**
     * Display the specified course
     */
    public function show(Course $course): JsonResponse
    {
        try {
            // Check if course belongs to current tenant
            if ($course->tenant_id !== Auth::user()->tenant_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Course not found'
                ], 404);
            }

            $course->load([
                'category',
                'users',
                'contents' => function ($query) {
                    $query->whereNull('parent_id')->orderBy('position');
                },
                'contents.children' => function ($query) {
                    $query->orderBy('position');
                },
                'sessions',
                'exams'
            ]);

            // Add additional statistics
            $course->statistics = [
                'total_students' => $course->users()->wherePivot('role', 'student')->count(),
                'total_instructors' => $course->users()->wherePivot('role', 'instructor')->count(),
                'total_content_items' => $course->contents()->count(),
                'total_sessions' => $course->sessions()->count(),
                'total_exams' => $course->exams()->count(),
                'total_duration_mins' => $course->contents()->sum('duration_mins')
            ];

            return response()->json([
                'success' => true,
                'data' => $course,
                'message' => 'Course retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error retrieving course: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving course'
            ], 500);
        }
    }

    /**
     * Update the specified course
     */
    public function update(Request $request, Course $course): JsonResponse
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
                'title' => 'sometimes|required|string|max:255',
                'description' => 'sometimes|required|string',
                'category_id' => 'sometimes|required|exists:categories,id',
                'schedule_level' => 'sometimes|required|in:beginner,intermediate,advanced',
                'is_active' => 'boolean',
                'instructors' => 'array',
                'instructors.*' => 'exists:users,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            $course->update($request->only([
                'title',
                'description',
                'category_id',
                'schedule_level',
                'is_active'
            ]));

            // Update instructors if provided
            if ($request->has('instructors')) {
                $course->users()->wherePivot('role', 'instructor')->detach();
                $instructors = collect($request->instructors)->mapWithKeys(function ($userId) {
                    return [$userId => ['role' => 'instructor']];
                });
                $course->users()->attach($instructors);
            }

            DB::commit();

            $course->load(['category', 'users']);

            return response()->json([
                'success' => true,
                'data' => $course,
                'message' => 'Course updated successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating course: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error updating course'
            ], 500);
        }
    }

    /**
     * Remove the specified course
     */
    public function destroy(Course $course): JsonResponse
    {
        try {
            // Check if course belongs to current tenant
            if ($course->tenant_id !== Auth::user()->tenant_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Course not found'
                ], 404);
            }

            // Check if course has enrolled students
            $hasStudents = $course->users()->wherePivot('role', 'student')->exists();
            if ($hasStudents) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete course with enrolled students'
                ], 409);
            }

            DB::beginTransaction();

            // Detach all users (instructors)
            $course->users()->detach();

            // Delete the course
            $course->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Course deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting course: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error deleting course'
            ], 500);
        }
    }

    /**
     * Get courses statistics for dashboard
     */
    public function statistics(): JsonResponse
    {
        try {
            $tenantId = Auth::user()->tenant_id;

            $stats = [
                'total_courses' => Course::where('tenant_id', $tenantId)->count(),
                'active_courses' => Course::where('tenant_id', $tenantId)->where('is_active', true)->count(),
                'inactive_courses' => Course::where('tenant_id', $tenantId)->where('is_active', false)->count(),
                'courses_by_level' => Course::where('tenant_id', $tenantId)
                    ->select('schedule_level', DB::raw('count(*) as count'))
                    ->groupBy('schedule_level')
                    ->get()
                    ->pluck('count', 'schedule_level'),
                'courses_by_category' => Course::where('tenant_id', $tenantId)
                    ->with('category')
                    ->select('category_id', DB::raw('count(*) as count'))
                    ->groupBy('category_id')
                    ->get()
                    ->map(function ($item) {
                        return [
                            'category_name' => $item->category->name ?? 'Uncategorized',
                            'count' => $item->count
                        ];
                    })
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'Course statistics retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error retrieving course statistics: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving course statistics'
            ], 500);
        }
    }

    /**
     * Enroll students in a course
     */
    public function enrollStudents(Request $request, Course $course): JsonResponse
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
                'student_ids' => 'required|array',
                'student_ids.*' => 'exists:users,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            $students = collect($request->student_ids)->mapWithKeys(function ($userId) {
                return [$userId => ['role' => 'student']];
            });

            $course->users()->syncWithoutDetaching($students);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Students enrolled successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error enrolling students: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error enrolling students'
            ], 500);
        }
    }

    /**
     * Get enrolled students for a course
     */
    public function getEnrolledStudents(Course $course): JsonResponse
    {
        try {
            // Check if course belongs to current tenant
            if ($course->tenant_id !== Auth::user()->tenant_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Course not found'
                ], 404);
            }

            $students = $course->users()
                ->wherePivot('role', 'student')
                ->with(['studentProgress' => function ($query) use ($course) {
                    $query->where('course_id', $course->id);
                }])
                ->get();

            return response()->json([
                'success' => true,
                'data' => $students,
                'message' => 'Enrolled students retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error retrieving enrolled students: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving enrolled students'
            ], 500);
        }
    }
}
