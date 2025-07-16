<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\User;
use App\Models\StudentProgress;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class EnrollmentController extends Controller
{
    /**
     * Display a listing of enrollments
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = DB::table('course_user')
                ->join('courses', 'course_user.course_id', '=', 'courses.id')
                ->join('users', 'course_user.user_id', '=', 'users.id')
                ->where('courses.tenant_id', Auth::user()->tenant_id)
                ->where('course_user.role', 'student')
                ->select(
                    'course_user.*',
                    'courses.title as course_title',
                    'courses.is_active as course_active',
                    'users.name as student_name',
                    'users.email as student_email'
                );

            // Filter by course
            if ($request->filled('course_id')) {
                $query->where('course_user.course_id', $request->course_id);
            }

            // Filter by student
            if ($request->filled('student_id')) {
                $query->where('course_user.user_id', $request->student_id);
            }

            // Search functionality
            if ($request->filled('search')) {
                $query->where(function ($q) use ($request) {
                    $q->where('users.name', 'like', '%' . $request->search . '%')
                      ->orWhere('users.email', 'like', '%' . $request->search . '%')
                      ->orWhere('courses.title', 'like', '%' . $request->search . '%');
                });
            }

            // Date range filter
            if ($request->filled('enrolled_from')) {
                $query->where('course_user.created_at', '>=', $request->enrolled_from);
            }

            if ($request->filled('enrolled_to')) {
                $query->where('course_user.created_at', '<=', $request->enrolled_to);
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            
            // Map frontend sort keys to database columns
            $sortMapping = [
                'created_at' => 'course_user.created_at',
                'student_name' => 'users.name',
                'course_title' => 'courses.title'
            ];
            
            $sortColumn = $sortMapping[$sortBy] ?? 'course_user.created_at';
            $query->orderBy($sortColumn, $sortOrder);

            // Pagination
            $perPage = $request->get('per_page', 15);
            $enrollments = $query->paginate($perPage);

            // Add progress information
            $enrollments->getCollection()->transform(function ($enrollment) {
                $progress = StudentProgress::where('user_id', $enrollment->user_id)
                    ->where('course_id', $enrollment->course_id)
                    ->first();

                $enrollment->progress = $progress ? [
                    'completion_percentage' => $progress->completion_percentage,
                    'last_accessed' => $progress->last_accessed,
                    'time_spent_mins' => $progress->time_spent_mins
                ] : null;

                return $enrollment;
            });

            return response()->json([
                'success' => true,
                'data' => $enrollments,
                'message' => 'Enrollments retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error retrieving enrollments: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving enrollments'
            ], 500);
        }
    }

    /**
     * Enroll a student in a course
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'course_id' => 'required|exists:courses,id',
                'student_id' => 'required|exists:users,id',
                'enrollment_date' => 'nullable|date'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Verify course belongs to current tenant
            $course = Course::where('id', $request->course_id)
                ->where('tenant_id', Auth::user()->tenant_id)
                ->first();

            if (!$course) {
                return response()->json([
                    'success' => false,
                    'message' => 'Course not found'
                ], 404);
            }

            // Verify student belongs to current tenant
            $student = User::where('id', $request->student_id)
                ->where('tenant_id', Auth::user()->tenant_id)
                ->first();

            if (!$student) {
                return response()->json([
                    'success' => false,
                    'message' => 'Student not found'
                ], 404);
            }

            // Check if student is already enrolled
            $existingEnrollment = DB::table('course_user')
                ->where('course_id', $request->course_id)
                ->where('user_id', $request->student_id)
                ->where('role', 'student')
                ->first();

            if ($existingEnrollment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Student is already enrolled in this course'
                ], 409);
            }

            DB::beginTransaction();

            // Create enrollment
            $course->users()->attach($request->student_id, [
                'role' => 'student',
                'created_at' => $request->enrollment_date ? now()->parse($request->enrollment_date) : now(),
                'updated_at' => now()
            ]);

            // Create initial progress record
            StudentProgress::create([
                'user_id' => $request->student_id,
                'course_id' => $request->course_id,
                'completion_percentage' => 0,
                'time_spent_mins' => 0,
                'last_accessed' => now(),
                'tenant_id' => Auth::user()->tenant_id
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Student enrolled successfully'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error enrolling student: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error enrolling student'
            ], 500);
        }
    }

    /**
     * Bulk enroll students in a course
     */
    public function bulkEnroll(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'course_id' => 'required|exists:courses,id',
                'student_ids' => 'required|array|min:1',
                'student_ids.*' => 'exists:users,id',
                'enrollment_date' => 'nullable|date'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Verify course belongs to current tenant
            $course = Course::where('id', $request->course_id)
                ->where('tenant_id', Auth::user()->tenant_id)
                ->first();

            if (!$course) {
                return response()->json([
                    'success' => false,
                    'message' => 'Course not found'
                ], 404);
            }

            // Verify all students belong to current tenant
            $students = User::whereIn('id', $request->student_ids)
                ->where('tenant_id', Auth::user()->tenant_id)
                ->pluck('id');

            if ($students->count() !== count($request->student_ids)) {
                return response()->json([
                    'success' => false,
                    'message' => 'One or more students not found'
                ], 404);
            }

            DB::beginTransaction();

            $enrollmentDate = $request->enrollment_date ? now()->parse($request->enrollment_date) : now();
            $successCount = 0;
            $skippedCount = 0;
            $errors = [];

            foreach ($request->student_ids as $studentId) {
                try {
                    // Check if student is already enrolled
                    $existingEnrollment = DB::table('course_user')
                        ->where('course_id', $request->course_id)
                        ->where('user_id', $studentId)
                        ->where('role', 'student')
                        ->first();

                    if ($existingEnrollment) {
                        $skippedCount++;
                        continue;
                    }

                    // Create enrollment
                    $course->users()->attach($studentId, [
                        'role' => 'student',
                        'created_at' => $enrollmentDate,
                        'updated_at' => now()
                    ]);

                    // Create initial progress record
                    StudentProgress::create([
                        'user_id' => $studentId,
                        'course_id' => $request->course_id,
                        'completion_percentage' => 0,
                        'time_spent_mins' => 0,
                        'last_accessed' => now(),
                        'tenant_id' => Auth::user()->tenant_id
                    ]);

                    $successCount++;

                } catch (\Exception $e) {
                    $errors[] = "Failed to enroll student ID $studentId: " . $e->getMessage();
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Bulk enrollment completed. $successCount enrolled, $skippedCount skipped.",
                'data' => [
                    'enrolled_count' => $successCount,
                    'skipped_count' => $skippedCount,
                    'errors' => $errors
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in bulk enrollment: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error in bulk enrollment'
            ], 500);
        }
    }

    /**
     * Unenroll a student from a course
     */
    public function destroy(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'course_id' => 'required|exists:courses,id',
                'student_id' => 'required|exists:users,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Verify course belongs to current tenant
            $course = Course::where('id', $request->course_id)
                ->where('tenant_id', Auth::user()->tenant_id)
                ->first();

            if (!$course) {
                return response()->json([
                    'success' => false,
                    'message' => 'Course not found'
                ], 404);
            }

            // Check if student is enrolled
            $enrollment = DB::table('course_user')
                ->where('course_id', $request->course_id)
                ->where('user_id', $request->student_id)
                ->where('role', 'student')
                ->first();

            if (!$enrollment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Student is not enrolled in this course'
                ], 404);
            }

            DB::beginTransaction();

            // Remove enrollment
            $course->users()->detach($request->student_id);

            // Delete progress record
            StudentProgress::where('user_id', $request->student_id)
                ->where('course_id', $request->course_id)
                ->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Student unenrolled successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error unenrolling student: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error unenrolling student'
            ], 500);
        }
    }

    /**
     * Get enrollment statistics
     */
    public function statistics(Request $request): JsonResponse
    {
        try {
            $tenantId = Auth::user()->tenant_id;

            // Base query for enrollments
            $enrollmentsQuery = DB::table('course_user')
                ->join('courses', 'course_user.course_id', '=', 'courses.id')
                ->where('courses.tenant_id', $tenantId)
                ->where('course_user.role', 'student');

            // Apply date filter if provided
            if ($request->filled('date_from')) {
                $enrollmentsQuery->where('course_user.created_at', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $enrollmentsQuery->where('course_user.created_at', '<=', $request->date_to);
            }

            $stats = [
                'total_enrollments' => $enrollmentsQuery->count(),
                'enrollments_by_month' => $enrollmentsQuery
                    ->select(DB::raw('YEAR(course_user.created_at) as year, MONTH(course_user.created_at) as month, COUNT(*) as count'))
                    ->groupBy('year', 'month')
                    ->orderBy('year', 'desc')
                    ->orderBy('month', 'desc')
                    ->limit(12)
                    ->get(),
                'enrollments_by_course' => $enrollmentsQuery
                    ->select('courses.title', DB::raw('COUNT(*) as count'))
                    ->groupBy('courses.id', 'courses.title')
                    ->orderBy('count', 'desc')
                    ->limit(10)
                    ->get(),
                'recent_enrollments' => $enrollmentsQuery
                    ->join('users', 'course_user.user_id', '=', 'users.id')
                    ->select(
                        'courses.title as course_title',
                        'users.name as student_name',
                        'course_user.created_at'
                    )
                    ->orderBy('course_user.created_at', 'desc')
                    ->limit(10)
                    ->get()
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'Enrollment statistics retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error retrieving enrollment statistics: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving enrollment statistics'
            ], 500);
        }
    }

    /**
     * Get student's enrollment history
     */
    public function studentHistory(User $student): JsonResponse
    {
        try {
            // Verify student belongs to current tenant
            if ($student->tenant_id !== Auth::user()->tenant_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Student not found'
                ], 404);
            }

            $enrollments = DB::table('course_user')
                ->join('courses', 'course_user.course_id', '=', 'courses.id')
                ->leftJoin('student_progress', function ($join) use ($student) {
                    $join->on('course_user.course_id', '=', 'student_progress.course_id')
                         ->where('student_progress.user_id', $student->id);
                })
                ->where('course_user.user_id', $student->id)
                ->where('course_user.role', 'student')
                ->where('courses.tenant_id', Auth::user()->tenant_id)
                ->select(
                    'courses.id as course_id',
                    'courses.title as course_title',
                    'courses.description as course_description',
                    'course_user.created_at as enrolled_at',
                    'student_progress.completion_percentage',
                    'student_progress.time_spent_mins',
                    'student_progress.last_accessed'
                )
                ->orderBy('course_user.created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $enrollments,
                'message' => 'Student enrollment history retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error retrieving student enrollment history: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving student enrollment history'
            ], 500);
        }
    }
}
