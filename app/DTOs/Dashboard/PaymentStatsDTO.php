<?php

namespace App\DTOs\Dashboard;

class PaymentStatsDTO
{
    public function __construct(
        public readonly float $totalRevenue,
        public readonly float $monthlyRevenue,
        public readonly int $pendingPayments,
        public readonly int $successfulPayments,
        public readonly int $failedPayments,
        public readonly float $averageOrderValue,
        public readonly float $revenueGrowth,
    ) {}

    public function toArray(): array
    {
        return [
            'totalRevenue' => $this->totalRevenue,
            'monthlyRevenue' => $this->monthlyRevenue,
            'pendingPayments' => $this->pendingPayments,
            'successfulPayments' => $this->successfulPayments,
            'failedPayments' => $this->failedPayments,
            'averageOrderValue' => $this->averageOrderValue,
            'revenueGrowth' => $this->revenueGrowth,
        ];
    }
}
