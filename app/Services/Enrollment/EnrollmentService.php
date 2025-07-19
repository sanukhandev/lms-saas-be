<?php

namespace App\Services\Enrollment;

use App\DTOs\Enrollment\EnrollmentDTO;
use App\DTOs\Enrollment\EnrollmentStatsDTO;
use App\Models\Course;
use App\Models\User;
use App\Models\StudentProgress;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EnrollmentService
{
    /**
     * Get enrollment list with caching
     */
    public function getEnrollmentsList(array $filters = []): array
    {
        $tenantId = Auth::user()->tenant_id;
        $cacheKey = "enrollments_list_{$tenantId}_" . md5(serialize($filters));
        
        return Cache::remember($cacheKey, 600, function () use ($filters, $tenantId) {
            try {
                $query = DB::table('course_user')
                    ->join('courses', 'course_user.course_id', '=', 'courses.id')
                    ->join('users', 'course_user.user_id', '=', 'users.id')
                    ->where('courses.tenant_id', $tenantId)
                    ->where('course_user.role', 'student')
                    ->select(
                        'course_user.*',
                        'courses.title as course_title',
                        'courses.slug as course_slug',
                        'users.name as student_name',
                        'users.email as student_email'
                    );

                // Apply filters
                if (!empty($filters['course_id'])) {
                    $query->where('course_user.course_id', $filters['course_id']);
                }

                if (!empty($filters['user_id'])) {
                    $query->where('course_user.user_id', $filters['user_id']);
                }

                if (!empty($filters['status'])) {
                    $query->where('course_user.status', $filters['status']);
                }

                if (!empty($filters['search'])) {
                    $query->where(function ($q) use ($filters) {
                        $q->where('courses.title', 'like', '%' . $filters['search'] . '%')
                          ->orWhere('users.name', 'like', '%' . $filters['search'] . '%')
                          ->orWhere('users.email', 'like', '%' . $filters['search'] . '%');
                    });
                }

                $query->orderBy('course_user.enrolled_at', 'desc');

                $perPage = $filters['per_page'] ?? 15;
                $enrollments = $query->paginate($perPage);

                // Transform to DTOs
                $enrollmentDTOs = $enrollments->getCollection()->map(function ($enrollment) {
                    return new EnrollmentDTO(
                        $enrollment->id,
                        $enrollment->user_id,
                        $enrollment->course_id,
                        $enrollment->status,
                        $enrollment->progress ?? 0,
                        $enrollment->grade ?? null,
                        $enrollment->enrolled_at ? new \DateTime($enrollment->enrolled_at) : null,
                        $enrollment->completed_at ? new \DateTime($enrollment->completed_at) : null,
                        $enrollment->student_name,
                        $enrollment->student_email,
                        $enrollment->course_title,
                        $enrollment->course_slug
                    );
                });

                return [
                    'success' => true,
                    'data' => [
                        'enrollments' => $enrollmentDTOs->map(fn($dto) => $dto->toArray()),
                        'pagination' => [
                            'current_page' => $enrollments->currentPage(),
                            'last_page' => $enrollments->lastPage(),
                            'per_page' => $enrollments->perPage(),
                            'total' => $enrollments->total(),
                            'from' => $enrollments->firstItem(),
                            'to' => $enrollments->lastItem()
                        ]
                    ]
                ];

            } catch (\Exception $e) {
                Log::error('Error fetching enrollments list', [
                    'tenant_id' => $tenantId,
                    'filters' => $filters,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                
                return ['success' => false, 'message' => 'Failed to fetch enrollments'];
            }
        });
    }

    /**
     * Get single enrollment by ID
     */
    public function getEnrollmentById(int $enrollmentId): array
    {
        $tenantId = Auth::user()->tenant_id;
        $cacheKey = "enrollment_{$enrollmentId}_{$tenantId}";
        
        return Cache::remember($cacheKey, 900, function () use ($enrollmentId, $tenantId) {
            try {
                $enrollment = DB::table('course_user')
                    ->join('courses', 'course_user.course_id', '=', 'courses.id')
                    ->join('users', 'course_user.user_id', '=', 'users.id')
                    ->where('course_user.id', $enrollmentId)
                    ->where('courses.tenant_id', $tenantId)
                    ->where('course_user.role', 'student')
                    ->select(
                        'course_user.*',
                        'courses.title as course_title',
                        'courses.slug as course_slug',
                        'users.name as student_name',
                        'users.email as student_email'
                    )
                    ->first();

                if (!$enrollment) {
                    return ['success' => false, 'message' => 'Enrollment not found'];
                }

                $enrollmentDTO = new EnrollmentDTO(
                    $enrollment->id,
                    $enrollment->user_id,
                    $enrollment->course_id,
                    $enrollment->status,
                    $enrollment->progress ?? 0,
                    $enrollment->grade ?? null,
                    $enrollment->enrolled_at ? new \DateTime($enrollment->enrolled_at) : null,
                    $enrollment->completed_at ? new \DateTime($enrollment->completed_at) : null,
                    $enrollment->student_name,
                    $enrollment->student_email,
                    $enrollment->course_title,
                    $enrollment->course_slug
                );

                return [
                    'success' => true,
                    'data' => $enrollmentDTO->toArray()
                ];

            } catch (\Exception $e) {
                Log::error('Error fetching enrollment', [
                    'enrollment_id' => $enrollmentId,
                    'tenant_id' => $tenantId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                
                return ['success' => false, 'message' => 'Failed to fetch enrollment'];
            }
        });
    }

    /**
     * Create new enrollment
     */
    public function createEnrollment(array $data): array
    {
        $tenantId = Auth::user()->tenant_id;
        
        try {
            // Validate course exists and belongs to tenant
            $course = Course::where('id', $data['course_id'])
                ->where('tenant_id', $tenantId)
                ->first();
                
            if (!$course) {
                return ['success' => false, 'message' => 'Course not found'];
            }

            // Validate user exists and belongs to tenant
            $user = User::where('id', $data['user_id'])
                ->where('tenant_id', $tenantId)
                ->first();
                
            if (!$user) {
                return ['success' => false, 'message' => 'User not found'];
            }

            // Check if already enrolled
            $existingEnrollment = DB::table('course_user')
                ->where('course_id', $data['course_id'])
                ->where('user_id', $data['user_id'])
                ->where('role', 'student')
                ->first();

            if ($existingEnrollment) {
                return ['success' => false, 'message' => 'User is already enrolled in this course'];
            }

            DB::beginTransaction();

            $enrollmentData = [
                'course_id' => $data['course_id'],
                'user_id' => $data['user_id'],
                'role' => 'student',
                'status' => $data['status'] ?? 'active',
                'progress' => 0,
                'enrolled_at' => now(),
            ];

            $enrollmentId = DB::table('course_user')->insertGetId($enrollmentData);

            DB::commit();

            // Clear related caches
            $this->clearEnrollmentCaches($tenantId, $data['course_id'], $data['user_id']);

            // Fetch the created enrollment for response
            $result = $this->getEnrollmentById($enrollmentId);
            
            if ($result['success']) {
                $result['message'] = 'Enrollment created successfully';
            }

            return $result;

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error creating enrollment', [
                'tenant_id' => $tenantId,
                'data' => $data,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return ['success' => false, 'message' => 'Failed to create enrollment'];
        }
    }

    /**
     * Update enrollment
     */
    public function updateEnrollment(int $enrollmentId, array $data): array
    {
        $tenantId = Auth::user()->tenant_id;
        
        try {
            // Check if enrollment exists and belongs to tenant
            $enrollment = DB::table('course_user')
                ->join('courses', 'course_user.course_id', '=', 'courses.id')
                ->where('course_user.id', $enrollmentId)
                ->where('courses.tenant_id', $tenantId)
                ->where('course_user.role', 'student')
                ->select('course_user.*')
                ->first();

            if (!$enrollment) {
                return ['success' => false, 'message' => 'Enrollment not found'];
            }

            DB::beginTransaction();

            $updateData = array_filter($data, function($value) {
                return $value !== null;
            });

            // Handle completion
            if (isset($data['status']) && $data['status'] === 'completed' && $enrollment->status !== 'completed') {
                $updateData['completed_at'] = now();
                $updateData['progress'] = 100;
            }

            DB::table('course_user')
                ->where('id', $enrollmentId)
                ->update($updateData);

            DB::commit();

            // Clear related caches
            $this->clearEnrollmentCaches($tenantId, $enrollment->course_id, $enrollment->user_id, $enrollmentId);

            // Fetch updated enrollment for response
            $result = $this->getEnrollmentById($enrollmentId);
            
            if ($result['success']) {
                $result['message'] = 'Enrollment updated successfully';
            }

            return $result;

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error updating enrollment', [
                'enrollment_id' => $enrollmentId,
                'tenant_id' => $tenantId,
                'data' => $data,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return ['success' => false, 'message' => 'Failed to update enrollment'];
        }
    }

    /**
     * Delete enrollment
     */
    public function deleteEnrollment(int $enrollmentId): array
    {
        $tenantId = Auth::user()->tenant_id;
        
        try {
            // Check if enrollment exists and belongs to tenant
            $enrollment = DB::table('course_user')
                ->join('courses', 'course_user.course_id', '=', 'courses.id')
                ->where('course_user.id', $enrollmentId)
                ->where('courses.tenant_id', $tenantId)
                ->where('course_user.role', 'student')
                ->select('course_user.*')
                ->first();

            if (!$enrollment) {
                return ['success' => false, 'message' => 'Enrollment not found'];
            }

            DB::beginTransaction();

            // Delete related student progress records
            StudentProgress::where('user_id', $enrollment->user_id)
                ->whereHas('courseContent', function ($query) use ($enrollment) {
                    $query->where('course_id', $enrollment->course_id);
                })
                ->delete();

            // Delete enrollment
            DB::table('course_user')->where('id', $enrollmentId)->delete();

            DB::commit();

            // Clear related caches
            $this->clearEnrollmentCaches($tenantId, $enrollment->course_id, $enrollment->user_id, $enrollmentId);

            return [
                'success' => true,
                'message' => 'Enrollment deleted successfully'
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error deleting enrollment', [
                'enrollment_id' => $enrollmentId,
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return ['success' => false, 'message' => 'Failed to delete enrollment'];
        }
    }

    /**
     * Get enrollment statistics
     */
    public function getEnrollmentStats(): array
    {
        $tenantId = Auth::user()->tenant_id;
        $cacheKey = "enrollment_stats_{$tenantId}";
        
        return Cache::remember($cacheKey, 900, function () use ($tenantId) {
            try {
                $totalEnrollments = DB::table('course_user')
                    ->join('courses', 'course_user.course_id', '=', 'courses.id')
                    ->where('courses.tenant_id', $tenantId)
                    ->where('course_user.role', 'student')
                    ->count();

                $activeEnrollments = DB::table('course_user')
                    ->join('courses', 'course_user.course_id', '=', 'courses.id')
                    ->where('courses.tenant_id', $tenantId)
                    ->where('course_user.role', 'student')
                    ->where('course_user.status', 'active')
                    ->count();

                $completedEnrollments = DB::table('course_user')
                    ->join('courses', 'course_user.course_id', '=', 'courses.id')
                    ->where('courses.tenant_id', $tenantId)
                    ->where('course_user.role', 'student')
                    ->where('course_user.status', 'completed')
                    ->count();

                $averageProgress = DB::table('course_user')
                    ->join('courses', 'course_user.course_id', '=', 'courses.id')
                    ->where('courses.tenant_id', $tenantId)
                    ->where('course_user.role', 'student')
                    ->avg('course_user.progress') ?? 0;

                // Enrollments by status
                $enrollmentsByStatus = DB::table('course_user')
                    ->join('courses', 'course_user.course_id', '=', 'courses.id')
                    ->where('courses.tenant_id', $tenantId)
                    ->where('course_user.role', 'student')
                    ->selectRaw('course_user.status, COUNT(*) as count')
                    ->groupBy('course_user.status')
                    ->pluck('count', 'status')
                    ->toArray();

                // Recent enrollments
                $recentEnrollments = DB::table('course_user')
                    ->join('courses', 'course_user.course_id', '=', 'courses.id')
                    ->join('users', 'course_user.user_id', '=', 'users.id')
                    ->where('courses.tenant_id', $tenantId)
                    ->where('course_user.role', 'student')
                    ->orderBy('course_user.enrolled_at', 'desc')
                    ->take(10)
                    ->select(
                        'course_user.id',
                        'users.name as student_name',
                        'courses.title as course_title',
                        'course_user.enrolled_at'
                    )
                    ->get()
                    ->toArray();

                $statsDTO = new EnrollmentStatsDTO(
                    $totalEnrollments,
                    $activeEnrollments,
                    $completedEnrollments,
                    round($averageProgress, 2),
                    $enrollmentsByStatus,
                    $recentEnrollments
                );

                return [
                    'success' => true,
                    'data' => $statsDTO->toArray()
                ];

            } catch (\Exception $e) {
                Log::error('Error fetching enrollment stats', [
                    'tenant_id' => $tenantId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                
                return ['success' => false, 'message' => 'Failed to fetch enrollment statistics'];
            }
        });
    }

    /**
     * Clear enrollment related caches
     */
    private function clearEnrollmentCaches(int $tenantId, ?int $courseId = null, ?int $userId = null, ?int $enrollmentId = null): void
    {
        try {
            // Clear specific enrollment cache if provided
            if ($enrollmentId) {
                Cache::forget("enrollment_{$enrollmentId}_{$tenantId}");
            }

            // Clear enrollment stats cache
            Cache::forget("enrollment_stats_{$tenantId}");

            // Clear list caches (pattern-based clearing)
            $keys = Cache::getRedis()->keys("*enrollments_list_{$tenantId}*");
            foreach ($keys as $key) {
                Cache::forget(str_replace(config('cache.prefix') . ':', '', $key));
            }

            // Clear course-specific caches if course_id provided
            if ($courseId) {
                Cache::forget("course_enrollments_{$courseId}_{$tenantId}");
            }

            // Clear user-specific caches if user_id provided
            if ($userId) {
                Cache::forget("user_enrollments_{$userId}_{$tenantId}");
            }

        } catch (\Exception $e) {
            Log::error('Error clearing enrollment caches', [
                'tenant_id' => $tenantId,
                'course_id' => $courseId,
                'user_id' => $userId,
                'enrollment_id' => $enrollmentId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
