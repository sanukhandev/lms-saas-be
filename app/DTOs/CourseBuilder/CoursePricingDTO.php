<?php

namespace App\DTOs\CourseBuilder;

use Carbon\Carbon;

class CoursePricingDTO
{
    public function __construct(
        public string $courseId,
        public string $accessModel,
        public float $basePrice,
        public string $baseCurrency,
        public float $discountPercentage,
        public ?float $discountedPrice,
        public ?float $subscriptionPrice,
        public ?int $trialPeriodDays,
        public bool $isActive,
        public array $enabledAccessModels,
        public Carbon $createdAt,
        public Carbon $updatedAt
    ) {}

    public function toArray(): array
    {
        return [
            'course_id' => $this->courseId,
            'access_model' => $this->accessModel,
            'base_price' => $this->basePrice,
            'base_currency' => $this->baseCurrency,
            'discount_percentage' => $this->discountPercentage,
            'discounted_price' => $this->discountedPrice,
            'subscription_price' => $this->subscriptionPrice,
            'trial_period_days' => $this->trialPeriodDays,
            'is_active' => $this->isActive,
            'enabled_access_models' => $this->enabledAccessModels,
            'created_at' => $this->createdAt->toISOString(),
            'updated_at' => $this->updatedAt->toISOString(),
        ];
    }
}
