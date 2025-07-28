<?php

namespace App\Services\Session;

use App\DTOs\Session\{SessionDTO, AttendanceDTO};
use App\Models\{ClassSession, SessionAttendance, User};
use Illuminate\Support\Facades\{DB, Cache};
use Illuminate\Support\Collection;
use Carbon\Carbon;

class SessionService
{
    private const CACHE_TTL = 60;

    /**
     * Start a class session
     */
    public function startSession(string $sessionId, string $tenantId): ?SessionDTO
    {
        DB::beginTransaction();

        try {
            $session = ClassSession::where('id', $sessionId)
                ->where('tenant_id', $tenantId)
                ->first();

            if (!$session) {
                return null;
            }

            if ($session->status !== 'scheduled') {
                throw new \Exception('Session is not in scheduled status');
            }

            $session->update([
                'status' => 'in_progress',
                'started_at' => now()
            ]);

            // Initialize attendance records for enrolled students
            $this->initializeAttendanceRecords($session);

            // Clear cache
            $this->clearSessionCache($sessionId);

            DB::commit();

            return $this->transformSessionToDTO($session->fresh(['tutor', 'content', 'attendances.student']));

        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * End a class session
     */
    public function endSession(string $sessionId, array $data, string $tenantId): ?SessionDTO
    {
        DB::beginTransaction();

        try {
            $session = ClassSession::where('id', $sessionId)
                ->where('tenant_id', $tenantId)
                ->first();

            if (!$session) {
                return null;
            }

            if ($session->status !== 'in_progress') {
                throw new \Exception('Session is not in progress');
            }

            $updateData = [
                'status' => 'completed',
                'ended_at' => now(),
                'summary' => $data['summary'] ?? null,
                'recording_url' => $data['recording_url'] ?? null,
                'homework_assigned' => $data['homework_assigned'] ?? null
            ];

            $session->update($updateData);

            // Mark absent students
            $this->markAbsentStudents($session);

            // Clear cache
            $this->clearSessionCache($sessionId);

            DB::commit();

            return $this->transformSessionToDTO($session->fresh(['tutor', 'content', 'attendances.student']));

        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Get session details
     */
    public function getSession(string $sessionId, string $tenantId): ?SessionDTO
    {
        $cacheKey = "t{$tenantId}:session:{$sessionId}";

        return Cache::tags(['sessions', "tenant:{$tenantId}"])->remember($cacheKey, self::CACHE_TTL, function () use ($sessionId, $tenantId) {
            $session = ClassSession::with(['tutor', 'content', 'attendances.student'])
                ->where('id', $sessionId)
                ->where('tenant_id', $tenantId)
                ->first();

            return $session ? $this->transformSessionToDTO($session) : null;
        });
    }

    /**
     * Update session details
     */
    public function updateSession(string $sessionId, array $data, string $tenantId): ?SessionDTO
    {
        $session = ClassSession::where('id', $sessionId)
            ->where('tenant_id', $tenantId)
            ->first();

        if (!$session) {
            return null;
        }

        $session->update($data);

        // Clear cache
        $this->clearSessionCache($sessionId);

        return $this->transformSessionToDTO($session->fresh(['tutor', 'content', 'attendances.student']));
    }

    /**
     * Mark student attendance
     */
    public function markAttendance(string $sessionId, array $data, string $tenantId): ?AttendanceDTO
    {
        DB::beginTransaction();

        try {
            $session = ClassSession::where('id', $sessionId)
                ->where('tenant_id', $tenantId)
                ->first();

            if (!$session) {
                return null;
            }

            // Verify student exists and has access to tenant
            $student = User::where('id', $data['student_id'])
                ->where('tenant_id', $tenantId)
                ->first();

            if (!$student) {
                throw new \Exception('Student not found');
            }

            $attendance = SessionAttendance::updateOrCreate(
                [
                    'session_id' => $sessionId,
                    'student_id' => $data['student_id'],
                    'tenant_id' => $tenantId
                ],
                [
                    'status' => $data['status'],
                    'joined_at' => $data['joined_at'] ?? null,
                    'left_at' => $data['left_at'] ?? null,
                    'notes' => $data['notes'] ?? null
                ]
            );

            // Update session statistics
            $this->updateSessionStatistics($session);

            // Clear cache
            $this->clearSessionCache($sessionId);

            DB::commit();

            return $this->transformAttendanceToDTO($attendance->fresh(['student']));

        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Bulk mark attendance
     */
    public function bulkMarkAttendance(string $sessionId, array $attendanceData, string $tenantId): Collection
    {
        DB::beginTransaction();

        try {
            $session = ClassSession::where('id', $sessionId)
                ->where('tenant_id', $tenantId)
                ->first();

            if (!$session) {
                throw new \Exception('Session not found');
            }

            $attendances = collect();

            foreach ($attendanceData['attendance'] as $data) {
                $attendance = SessionAttendance::updateOrCreate(
                    [
                        'session_id' => $sessionId,
                        'student_id' => $data['student_id'],
                        'tenant_id' => $tenantId
                    ],
                    [
                        'status' => $data['status'],
                        'joined_at' => $data['joined_at'] ?? null,
                        'left_at' => $data['left_at'] ?? null,
                        'notes' => $data['notes'] ?? null
                    ]
                );

                $attendances->push($this->transformAttendanceToDTO($attendance->fresh(['student'])));
            }

            // Update session statistics
            $this->updateSessionStatistics($session);

            // Clear cache
            $this->clearSessionCache($sessionId);

            DB::commit();

            return $attendances;

        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Get session attendance
     */
    public function getSessionAttendance(string $sessionId, string $tenantId): Collection
    {
        $cacheKey = "session_attendance_{$sessionId}_{$tenantId}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($sessionId, $tenantId) {
            $attendances = SessionAttendance::with(['student'])
                ->where('session_id', $sessionId)
                ->where('tenant_id', $tenantId)
                ->get();

            return $attendances->map(fn($attendance) => $this->transformAttendanceToDTO($attendance));
        });
    }

    /**
     * Get student attendance for course
     */
    public function getStudentAttendance(string $courseId, string $studentId, string $tenantId): Collection
    {
        $cacheKey = "student_attendance_{$courseId}_{$studentId}_{$tenantId}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($courseId, $studentId, $tenantId) {
            $attendances = SessionAttendance::with(['session.content'])
                ->whereHas('session', function ($query) use ($courseId) {
                    $query->where('course_id', $courseId);
                })
                ->where('student_id', $studentId)
                ->where('tenant_id', $tenantId)
                ->orderBy('created_at', 'desc')
                ->get();

            return $attendances->map(fn($attendance) => $this->transformAttendanceToDTO($attendance));
        });
    }

    /**
     * Get course attendance statistics
     */
    public function getCourseAttendanceStats(string $courseId, string $tenantId): array
    {
        $cacheKey = "course_attendance_stats_{$courseId}_{$tenantId}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($courseId, $tenantId) {
            $sessions = ClassSession::where('course_id', $courseId)
                ->where('tenant_id', $tenantId)
                ->where('status', 'completed')
                ->withCount(['attendances as total_attendances', 'attendances as present_count' => function ($query) {
                    $query->where('status', 'present');
                }])
                ->get();

            $totalSessions = $sessions->count();
            $totalAttendances = $sessions->sum('total_attendances');
            $totalPresent = $sessions->sum('present_count');

            return [
                'totalSessions' => $totalSessions,
                'totalAttendances' => $totalAttendances,
                'totalPresent' => $totalPresent,
                'attendanceRate' => $totalAttendances > 0 ? round(($totalPresent / $totalAttendances) * 100, 2) : 0,
                'averageAttendancePerSession' => $totalSessions > 0 ? round($totalAttendances / $totalSessions, 2) : 0
            ];
        });
    }

    /**
     * Record session feedback
     */
    public function recordSessionFeedback(string $sessionId, array $data, string $tenantId): bool
    {
        $session = ClassSession::where('id', $sessionId)
            ->where('tenant_id', $tenantId)
            ->first();

        if (!$session) {
            return false;
        }

        $session->update([
            'feedback_rating' => $data['rating'] ?? null,
            'feedback_comments' => $data['comments'] ?? null,
            'feedback_submitted_at' => now()
        ]);

        // Clear cache
        $this->clearSessionCache($sessionId);

        return true;
    }

    /**
     * Initialize attendance records for enrolled students
     */
    private function initializeAttendanceRecords(ClassSession $session): void
    {
        // Get enrolled students for the course
        $enrolledStudents = DB::table('course_enrollments')
            ->where('course_id', $session->course_id)
            ->where('tenant_id', $session->tenant_id)
            ->where('status', 'active')
            ->pluck('student_id');

        foreach ($enrolledStudents as $studentId) {
            SessionAttendance::firstOrCreate(
                [
                    'session_id' => $session->id,
                    'student_id' => $studentId,
                    'tenant_id' => $session->tenant_id
                ],
                [
                    'status' => 'pending'
                ]
            );
        }
    }

    /**
     * Mark students as absent if not marked present
     */
    private function markAbsentStudents(ClassSession $session): void
    {
        SessionAttendance::where('session_id', $session->id)
            ->where('tenant_id', $session->tenant_id)
            ->where('status', 'pending')
            ->update(['status' => 'absent']);
    }

    /**
     * Update session statistics
     */
    private function updateSessionStatistics(ClassSession $session): void
    {
        $attendanceStats = SessionAttendance::where('session_id', $session->id)
            ->selectRaw('
                COUNT(*) as total_students,
                SUM(CASE WHEN status = "present" THEN 1 ELSE 0 END) as present_count,
                SUM(CASE WHEN status = "absent" THEN 1 ELSE 0 END) as absent_count,
                SUM(CASE WHEN status = "late" THEN 1 ELSE 0 END) as late_count
            ')
            ->first();

        $session->update([
            'total_students' => $attendanceStats->total_students ?? 0,
            'present_count' => $attendanceStats->present_count ?? 0,
            'absent_count' => $attendanceStats->absent_count ?? 0,
            'late_count' => $attendanceStats->late_count ?? 0
        ]);
    }

    /**
     * Clear session-related cache
     */
    private function clearSessionCache(string $sessionId): void
    {
        $session = ClassSession::find($sessionId);
        if ($session) {
            Cache::forget("session_{$sessionId}_{$session->tenant_id}");
            Cache::forget("session_attendance_{$sessionId}_{$session->tenant_id}");
            Cache::forget("course_attendance_stats_{$session->course_id}_{$session->tenant_id}");
        }
    }

    /**
     * Transform ClassSession model to SessionDTO
     */
    private function transformSessionToDTO(ClassSession $session): SessionDTO
    {
        return new SessionDTO(
            id: $session->id,
            courseId: $session->course_id,
            contentId: $session->content_id,
            tutorId: $session->tutor_id,
            tutorName: $session->tutor->name ?? null,
            contentTitle: $session->content->title ?? null,
            scheduledAt: $session->scheduled_at,
            startedAt: $session->started_at,
            endedAt: $session->ended_at,
            durationMins: $session->duration_mins,
            meetingUrl: $session->meeting_url,
            isRecorded: $session->is_recorded,
            recordingUrl: $session->recording_url,
            status: $session->status,
            summary: $session->summary,
            homeworkAssigned: $session->homework_assigned,
            feedbackRating: $session->feedback_rating,
            feedbackComments: $session->feedback_comments,
            totalStudents: $session->total_students ?? 0,
            presentCount: $session->present_count ?? 0,
            absentCount: $session->absent_count ?? 0,
            lateCount: $session->late_count ?? 0,
            attendances: $session->attendances ? $session->attendances->map(fn($attendance) => $this->transformAttendanceToDTO($attendance)) : collect(),
            createdAt: $session->created_at,
            updatedAt: $session->updated_at
        );
    }

    /**
     * Transform SessionAttendance model to AttendanceDTO
     */
    private function transformAttendanceToDTO(SessionAttendance $attendance): AttendanceDTO
    {
        return new AttendanceDTO(
            id: $attendance->id,
            sessionId: $attendance->session_id,
            studentId: $attendance->student_id,
            studentName: $attendance->student->name ?? null,
            status: $attendance->status,
            joinedAt: $attendance->joined_at,
            leftAt: $attendance->left_at,
            notes: $attendance->notes,
            createdAt: $attendance->created_at,
            updatedAt: $attendance->updated_at
        );
    }
}
