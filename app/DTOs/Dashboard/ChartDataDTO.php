<?php

namespace App\DTOs\Dashboard;

class ChartDataDTO
{
    public function __construct(
        public readonly array $enrollmentTrends,
        public readonly array $completionTrends,
        public readonly array $revenueTrends,
        public readonly array $categoryDistribution,
        public readonly array $userActivityTrends,
        public readonly array $monthlyStats
    ) {}

    public function toArray(): array
    {
        return [
            'enrollment_trends' => $this->enrollmentTrends,
            'completion_trends' => $this->completionTrends,
            'revenue_trends' => $this->revenueTrends,
            'category_distribution' => $this->categoryDistribution,
            'user_activity_trends' => $this->userActivityTrends,
            'monthly_stats' => $this->monthlyStats,
        ];
    }
}
