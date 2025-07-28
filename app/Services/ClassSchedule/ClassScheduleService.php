<?php

namespace App\Services\ClassSchedule;

use App\DTOs\ClassSchedule\{ClassSessionDTO, TeachingPlanDTO};
use App\Models\{ClassSession, TeachingPlan, Course, CourseContent, User};
use Illuminate\Support\Facades\{DB, Cache, Notification};
use Illuminate\Support\Collection;
use Carbon\Carbon;

class ClassScheduleService
{
    private const CACHE_TTL = 300;

    /**
     * Get all classes for a course
     */
    public function getCourseClasses(string $courseId, string $tenantId): Collection
    {
        $cacheKey = "course_classes_{$courseId}_{$tenantId}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($courseId, $tenantId) {
            $sessions = ClassSession::with(['tutor', 'content', 'students'])
                ->where('course_id', $courseId)
                ->where('tenant_id', $tenantId)
                ->orderBy('scheduled_at')
                ->get();

            return $sessions->map(fn($session) => $this->transformSessionToDTO($session));
        });
    }

    /**
     * Get classes for specific content
     */
    public function getContentClasses(string $courseId, string $contentId, string $tenantId): Collection
    {
        $cacheKey = "content_classes_{$courseId}_{$contentId}_{$tenantId}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($courseId, $contentId, $tenantId) {
            $sessions = ClassSession::with(['tutor', 'content', 'students'])
                ->where('course_id', $courseId)
                ->where('content_id', $contentId)
                ->where('tenant_id', $tenantId)
                ->orderBy('scheduled_at')
                ->get();

            return $sessions->map(fn($session) => $this->transformSessionToDTO($session));
        });
    }

    /**
     * Schedule a new class
     */
    public function scheduleClass(array $data, string $tenantId): ClassSessionDTO
    {
        DB::beginTransaction();

        try {
            // Validate course exists and belongs to tenant
            $course = Course::where('id', $data['course_id'])
                ->where('tenant_id', $tenantId)
                ->first();

            if (!$course) {
                throw new \Exception('Course not found');
            }

            // Validate tutor exists and has access to tenant
            $tutor = User::where('id', $data['tutor_id'])
                ->where('tenant_id', $tenantId)
                ->first();

            if (!$tutor) {
                throw new \Exception('Tutor not found');
            }

            // Check for scheduling conflicts
            $hasConflict = $this->checkSchedulingConflict(
                $data['tutor_id'],
                $data['scheduled_at'],
                $data['duration_mins']
            );

            if ($hasConflict) {
                throw new \Exception('Tutor has a scheduling conflict at this time');
            }

            $sessionData = [
                'tenant_id' => $tenantId,
                'course_id' => $data['course_id'],
                'content_id' => $data['content_id'] ?? null,
                'tutor_id' => $data['tutor_id'],
                'scheduled_at' => $data['scheduled_at'],
                'duration_mins' => $data['duration_mins'],
                'meeting_url' => $data['meeting_url'] ?? null,
                'is_recorded' => $data['is_recorded'] ?? false,
                'status' => $data['status'] ?? 'scheduled',
            ];

            $session = ClassSession::create($sessionData);

            // Handle recurring sessions
            if ($data['recurring'] ?? false) {
                $this->createRecurringSessions($session, $data, $tenantId);
            }

            // Clear cache
            $this->clearClassCache($data['course_id'], $tenantId);

            // Send notifications if requested
            if ($data['send_notifications'] ?? false) {
                $this->sendClassScheduleNotification($session);
            }

            DB::commit();

            return $this->transformSessionToDTO($session->fresh(['tutor', 'content', 'students']));

        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Update scheduled class
     */
    public function updateSchedule(string $sessionId, array $data, string $tenantId): ?ClassSessionDTO
    {
        DB::beginTransaction();

        try {
            $session = ClassSession::where('id', $sessionId)
                ->where('tenant_id', $tenantId)
                ->first();

            if (!$session) {
                return null;
            }

            // Check for conflicts if tutor or time is being changed
            if (isset($data['tutor_id']) || isset($data['scheduled_at']) || isset($data['duration_mins'])) {
                $tutorId = $data['tutor_id'] ?? $session->tutor_id;
                $scheduledAt = $data['scheduled_at'] ?? $session->scheduled_at;
                $duration = $data['duration_mins'] ?? $session->duration_mins;

                $hasConflict = $this->checkSchedulingConflict($tutorId, $scheduledAt, $duration, $sessionId);

                if ($hasConflict) {
                    throw new \Exception('Tutor has a scheduling conflict at this time');
                }
            }

            $session->update($data);

            // Clear cache
            $this->clearClassCache($session->course_id, $tenantId);

            DB::commit();

            return $this->transformSessionToDTO($session->fresh(['tutor', 'content', 'students']));

        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Cancel a scheduled class
     */
    public function cancelClass(string $sessionId, string $tenantId): bool
    {
        DB::beginTransaction();

        try {
            $session = ClassSession::where('id', $sessionId)
                ->where('tenant_id', $tenantId)
                ->first();

            if (!$session) {
                return false;
            }

            $session->update([
                'status' => 'cancelled',
                'cancelled_at' => now()
            ]);

            // Clear cache
            $this->clearClassCache($session->course_id, $tenantId);

            // Send cancellation notifications
            $this->sendClassCancellationNotification($session);

            DB::commit();

            return true;

        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Get class planner for course
     */
    public function getClassPlanner(string $courseId, string $tenantId): Collection
    {
        $cacheKey = "class_planner_{$courseId}_{$tenantId}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($courseId, $tenantId) {
            $plans = TeachingPlan::with(['instructor', 'content'])
                ->where('course_id', $courseId)
                ->where('tenant_id', $tenantId)
                ->orderBy('planned_date')
                ->get();

            return $plans->map(fn($plan) => $this->transformPlanToDTO($plan));
        });
    }

    /**
     * Create teaching plan
     */
    public function createTeachingPlan(array $data, string $tenantId): TeachingPlanDTO
    {
        $planData = [
            'tenant_id' => $tenantId,
            'course_id' => $data['course_id'],
            'content_id' => $data['content_id'] ?? null,
            'instructor_id' => $data['instructor_id'],
            'class_type' => $data['class_type'],
            'planned_date' => $data['planned_date'],
            'duration_mins' => $data['duration_mins'],
            'learning_objectives' => $data['learning_objectives'] ?? null,
            'prerequisites' => $data['prerequisites'] ?? null,
            'materials_needed' => $data['materials_needed'] ?? null,
            'notes' => $data['notes'] ?? null,
            'priority' => $data['priority'] ?? 3,
            'is_flexible' => $data['is_flexible'] ?? false,
        ];

        $plan = TeachingPlan::create($planData);

        // Clear cache
        $this->clearPlannerCache($data['course_id'], $tenantId);

        return $this->transformPlanToDTO($plan->fresh(['instructor', 'content']));
    }

    /**
     * Update teaching plan
     */
    public function updateTeachingPlan(string $planId, array $data, string $tenantId): ?TeachingPlanDTO
    {
        $plan = TeachingPlan::where('id', $planId)
            ->where('tenant_id', $tenantId)
            ->first();

        if (!$plan) {
            return null;
        }

        $plan->update($data);

        // Clear cache
        $this->clearPlannerCache($plan->course_id, $tenantId);

        return $this->transformPlanToDTO($plan->fresh(['instructor', 'content']));
    }

    /**
     * Delete teaching plan
     */
    public function deleteTeachingPlan(string $planId, string $tenantId): bool
    {
        $plan = TeachingPlan::where('id', $planId)
            ->where('tenant_id', $tenantId)
            ->first();

        if (!$plan) {
            return false;
        }

        $courseId = $plan->course_id;
        $plan->delete();

        // Clear cache
        $this->clearPlannerCache($courseId, $tenantId);

        return true;
    }

    /**
     * Bulk schedule classes from teaching plans
     */
    public function bulkScheduleClasses(array $data, string $tenantId): Collection
    {
        DB::beginTransaction();

        try {
            $sessions = collect();

            foreach ($data['tutor_assignments'] as $assignment) {
                $plan = TeachingPlan::where('id', $assignment['plan_id'])
                    ->where('tenant_id', $tenantId)
                    ->first();

                if (!$plan) {
                    continue;
                }

                $sessionData = [
                    'tenant_id' => $tenantId,
                    'course_id' => $data['course_id'],
                    'content_id' => $plan->content_id,
                    'tutor_id' => $assignment['tutor_id'],
                    'scheduled_at' => $assignment['scheduled_at'],
                    'duration_mins' => $plan->duration_mins,
                    'meeting_url' => $assignment['meeting_url'] ?? null,
                    'is_recorded' => false,
                    'status' => 'scheduled',
                ];

                // Check for conflicts
                $hasConflict = $this->checkSchedulingConflict(
                    $assignment['tutor_id'],
                    $assignment['scheduled_at'],
                    $plan->duration_mins
                );

                if (!$hasConflict) {
                    $session = ClassSession::create($sessionData);
                    $sessions->push($this->transformSessionToDTO($session->fresh(['tutor', 'content'])));
                    
                    // Update plan status
                    $plan->update(['status' => 'scheduled']);
                }
            }

            // Clear cache
            $this->clearClassCache($data['course_id'], $tenantId);

            // Send notifications if requested
            if ($data['send_notifications'] ?? false) {
                $sessions->each(fn($session) => $this->sendClassScheduleNotification($session));
            }

            DB::commit();

            return $sessions;

        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Check for scheduling conflicts
     */
    private function checkSchedulingConflict(string $tutorId, string $scheduledAt, int $durationMins, ?string $excludeSessionId = null): bool
    {
        $scheduledStart = Carbon::parse($scheduledAt);
        $scheduledEnd = $scheduledStart->copy()->addMinutes($durationMins);

        $query = ClassSession::where('tutor_id', $tutorId)
            ->where('status', '!=', 'cancelled')
            ->where(function ($q) use ($scheduledStart, $scheduledEnd) {
                $q->whereBetween('scheduled_at', [$scheduledStart, $scheduledEnd])
                  ->orWhere(function ($q2) use ($scheduledStart, $scheduledEnd) {
                      $q2->where('scheduled_at', '<=', $scheduledStart)
                         ->whereRaw('DATE_ADD(scheduled_at, INTERVAL duration_mins MINUTE) >= ?', [$scheduledStart]);
                  });
            });

        if ($excludeSessionId) {
            $query->where('id', '!=', $excludeSessionId);
        }

        return $query->exists();
    }

    /**
     * Create recurring sessions
     */
    private function createRecurringSessions(ClassSession $baseSession, array $data, string $tenantId): void
    {
        if (!isset($data['recurring_type']) || !isset($data['recurring_end_date'])) {
            return;
        }

        $startDate = Carbon::parse($baseSession->scheduled_at);
        $endDate = Carbon::parse($data['recurring_end_date']);
        $interval = match ($data['recurring_type']) {
            'daily' => 'addDay',
            'weekly' => 'addWeek',
            'monthly' => 'addMonth',
            default => null
        };

        if (!$interval) {
            return;
        }

        $currentDate = $startDate->copy()->$interval();

        while ($currentDate->lte($endDate)) {
            // Check for conflicts before creating
            $hasConflict = $this->checkSchedulingConflict(
                $baseSession->tutor_id,
                $currentDate->toDateTimeString(),
                $baseSession->duration_mins
            );

            if (!$hasConflict) {
                ClassSession::create([
                    'tenant_id' => $tenantId,
                    'course_id' => $baseSession->course_id,
                    'content_id' => $baseSession->content_id,
                    'tutor_id' => $baseSession->tutor_id,
                    'scheduled_at' => $currentDate->toDateTimeString(),
                    'duration_mins' => $baseSession->duration_mins,
                    'meeting_url' => $baseSession->meeting_url,
                    'is_recorded' => $baseSession->is_recorded,
                    'status' => 'scheduled',
                ]);
            }

            $currentDate->$interval();
        }
    }

    /**
     * Clear class-related cache
     */
    private function clearClassCache(string $courseId, string $tenantId): void
    {
        Cache::forget("course_classes_{$courseId}_{$tenantId}");
        Cache::forget("class_planner_{$courseId}_{$tenantId}");
    }

    /**
     * Clear planner cache
     */
    private function clearPlannerCache(string $courseId, string $tenantId): void
    {
        Cache::forget("class_planner_{$courseId}_{$tenantId}");
    }

    /**
     * Send class schedule notification
     */
    private function sendClassScheduleNotification(ClassSession $session): void
    {
        // Implementation for sending notifications
        // This would integrate with your notification system
    }

    /**
     * Send class cancellation notification
     */
    private function sendClassCancellationNotification(ClassSession $session): void
    {
        // Implementation for sending cancellation notifications
        // This would integrate with your notification system
    }

    /**
     * Transform ClassSession model to DTO
     */
    private function transformSessionToDTO(ClassSession $session): ClassSessionDTO
    {
        return new ClassSessionDTO(
            id: $session->id,
            courseId: $session->course_id,
            contentId: $session->content_id,
            tutorId: $session->tutor_id,
            tutorName: $session->tutor->name ?? null,
            contentTitle: $session->content->title ?? null,
            scheduledAt: $session->scheduled_at,
            durationMins: $session->duration_mins,
            meetingUrl: $session->meeting_url,
            isRecorded: $session->is_recorded,
            recordingUrl: $session->recording_url,
            status: $session->status,
            studentsCount: $session->students->count(),
            createdAt: $session->created_at,
            updatedAt: $session->updated_at
        );
    }

    /**
     * Transform TeachingPlan model to DTO
     */
    private function transformPlanToDTO(TeachingPlan $plan): TeachingPlanDTO
    {
        return new TeachingPlanDTO(
            id: $plan->id,
            courseId: $plan->course_id,
            contentId: $plan->content_id,
            instructorId: $plan->instructor_id,
            instructorName: $plan->instructor->name ?? null,
            contentTitle: $plan->content->title ?? null,
            classType: $plan->class_type,
            plannedDate: $plan->planned_date,
            durationMins: $plan->duration_mins,
            learningObjectives: $plan->learning_objectives,
            prerequisites: $plan->prerequisites,
            materialsNeeded: $plan->materials_needed,
            notes: $plan->notes,
            priority: $plan->priority,
            isFlexible: $plan->is_flexible,
            status: $plan->status ?? 'planned',
            createdAt: $plan->created_at,
            updatedAt: $plan->updated_at
        );
    }
}
