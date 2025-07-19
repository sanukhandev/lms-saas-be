<?php

namespace App\DTOs\User;

class UserStatsDTO
{
    public function __construct(
        public readonly int $totalUsers,
        public readonly int $activeUsers,
        public readonly int $verifiedUsers,
        public readonly array $usersByRole,
        public readonly int $recentRegistrations,
        public readonly float $growthRate,
    ) {}

    public function toArray(): array
    {
        return [
            'total_users' => $this->totalUsers,
            'active_users' => $this->activeUsers,
            'verified_users' => $this->verifiedUsers,
            'users_by_role' => $this->usersByRole,
            'recent_registrations' => $this->recentRegistrations,
            'growth_rate' => $this->growthRate,
        ];
    }
}
