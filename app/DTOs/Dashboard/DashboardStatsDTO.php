<?php

namespace App\DTOs\Dashboard;

class DashboardStatsDTO
{
    public function __construct(
        public readonly int $totalUsers,
        public readonly int $totalCourses,
        public readonly int $totalEnrollments,
        public readonly float $totalRevenue,
        public readonly float $userGrowthRate,
        public readonly float $courseCompletionRate,
        public readonly int $activeUsers,
        public readonly int $pendingEnrollments,
    ) {}

    public function toArray(): array
    {
        return [
            'totalUsers' => $this->totalUsers,
            'totalCourses' => $this->totalCourses,
            'totalEnrollments' => $this->totalEnrollments,
            'totalRevenue' => $this->totalRevenue,
            'userGrowthRate' => $this->userGrowthRate,
            'courseCompletionRate' => $this->courseCompletionRate,
            'activeUsers' => $this->activeUsers,
            'pendingEnrollments' => $this->pendingEnrollments,
        ];
    }
}
