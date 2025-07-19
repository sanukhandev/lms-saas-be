<?php

namespace App\DTOs\Enrollment;

class EnrollmentStatsDTO
{
    public function __construct(
        public readonly int $totalEnrollments,
        public readonly int $activeEnrollments,
        public readonly int $completedEnrollments,
        public readonly float $averageProgress,
        public readonly array $enrollmentsByStatus,
        public readonly array $recentEnrollments
    ) {}

    public function toArray(): array
    {
        return [
            'total_enrollments' => $this->totalEnrollments,
            'active_enrollments' => $this->activeEnrollments,
            'completed_enrollments' => $this->completedEnrollments,
            'average_progress' => $this->averageProgress,
            'completion_rate' => $this->totalEnrollments > 0 
                ? round(($this->completedEnrollments / $this->totalEnrollments) * 100, 2) 
                : 0,
            'enrollments_by_status' => $this->enrollmentsByStatus,
            'recent_enrollments' => $this->recentEnrollments
        ];
    }
}
