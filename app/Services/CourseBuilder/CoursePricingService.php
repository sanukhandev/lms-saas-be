<?php

namespace App\Services\CourseBuilder;

use App\Models\Course;
use App\Models\Tenant;
use App\DTOs\CourseBuilder\CoursePricingDTO;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CoursePricingService
{
    private const CACHE_TTL = 300; // 5 minutes

    /**
     * Get course pricing information
     */
    public function getCoursePricing(string $courseId): CoursePricingDTO
    {
        $tenantId = Auth::user()->tenant_id;
        $cacheKey = "course_pricing_{$courseId}_{$tenantId}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($courseId, $tenantId) {
            $course = Course::where('id', $courseId)
                ->where('tenant_id', $tenantId)
                ->firstOrFail();

            $tenant = Tenant::find($tenantId);

            return new CoursePricingDTO(
                courseId: $course->id,
                accessModel: $course->access_model ?? 'one_time',
                basePrice: $course->price ?? 0,
                baseCurrency: $tenant->currency ?? 'USD',
                discountPercentage: $course->discount_percentage ?? 0,
                discountedPrice: $this->calculateDiscountedPrice($course->price ?? 0, $course->discount_percentage ?? 0),
                subscriptionPrice: $course->subscription_price ?? null,
                trialPeriodDays: $course->trial_period_days ?? null,
                isActive: $course->is_pricing_active ?? false,
                enabledAccessModels: $this->getEnabledAccessModels($tenant),
                createdAt: $course->created_at,
                updatedAt: $course->updated_at
            );
        });
    }

    /**
     * Update course pricing
     */
    public function updateCoursePricing(string $courseId, array $data): CoursePricingDTO
    {
        $tenantId = Auth::user()->tenant_id;

        $course = Course::where('id', $courseId)
            ->where('tenant_id', $tenantId)
            ->firstOrFail();

        $tenant = Tenant::find($tenantId);

        // Validate access model is enabled for tenant
        $this->validateAccessModel($data['access_model'], $tenant);

        return DB::transaction(function () use ($course, $data) {
            $updateData = [
                'access_model' => $data['access_model'],
                'is_pricing_active' => $data['is_active'] ?? false,
            ];

            // Handle pricing based on access model
            switch ($data['access_model']) {
                case 'one_time':
                    $updateData['price'] = $data['base_price'];
                    $updateData['discount_percentage'] = $data['discount_percentage'] ?? 0;
                    $updateData['subscription_price'] = null;
                    $updateData['trial_period_days'] = null;
                    break;

                case 'monthly_subscription':
                    $updateData['subscription_price'] = $data['subscription_price'];
                    $updateData['trial_period_days'] = $data['trial_period_days'] ?? null;
                    $updateData['price'] = null;
                    $updateData['discount_percentage'] = 0;
                    break;

                case 'full_curriculum':
                    // For curriculum access, pricing is handled at tenant level
                    $updateData['price'] = null;
                    $updateData['subscription_price'] = null;
                    $updateData['discount_percentage'] = 0;
                    $updateData['trial_period_days'] = null;
                    break;
            }

            $course->update($updateData);

            $this->clearPricingCache($course->id, $course->tenant_id);

            return $this->getCoursePricing($course->id);
        });
    }

    /**
     * Calculate course price in user's local currency
     */
    public function calculateLocalPrice(string $courseId, string $userCurrency): array
    {
        $pricing = $this->getCoursePricing($courseId);

        if ($pricing->baseCurrency === $userCurrency) {
            return [
                'base_price' => $pricing->basePrice,
                'discounted_price' => $pricing->discountedPrice,
                'subscription_price' => $pricing->subscriptionPrice,
                'currency' => $pricing->baseCurrency,
                'exchange_rate' => 1.0,
            ];
        }

        $exchangeRate = $this->getExchangeRate($pricing->baseCurrency, $userCurrency);

        return [
            'base_price' => $pricing->basePrice ? round($pricing->basePrice * $exchangeRate, 2) : null,
            'discounted_price' => $pricing->discountedPrice ? round($pricing->discountedPrice * $exchangeRate, 2) : null,
            'subscription_price' => $pricing->subscriptionPrice ? round($pricing->subscriptionPrice * $exchangeRate, 2) : null,
            'currency' => $userCurrency,
            'exchange_rate' => $exchangeRate,
        ];
    }

    /**
     * Get bulk pricing for multiple courses
     */
    public function getBulkCoursePricing(array $courseIds, string $userCurrency = null): array
    {
        $tenantId = Auth::user()->tenant_id;
        $tenant = Tenant::find($tenantId);
        $targetCurrency = $userCurrency ?? $tenant->currency ?? 'USD';

        $courses = Course::whereIn('id', $courseIds)
            ->where('tenant_id', $tenantId)
            ->get();

        $pricingData = [];

        foreach ($courses as $course) {
            $pricing = $this->getCoursePricing($course->id);
            $localPricing = $this->calculateLocalPrice($course->id, $targetCurrency);

            $pricingData[] = [
                'course_id' => $course->id,
                'title' => $course->title,
                'access_model' => $pricing->accessModel,
                'pricing' => $localPricing,
                'is_active' => $pricing->isActive,
            ];
        }

        return $pricingData;
    }

    /**
     * Validate course pricing configuration
     */
    public function validatePricingConfiguration(array $data): array
    {
        $errors = [];

        switch ($data['access_model']) {
            case 'one_time':
                if (!isset($data['base_price']) || $data['base_price'] <= 0) {
                    $errors[] = 'Base price is required for one-time purchase model';
                }

                if (isset($data['discount_percentage'])) {
                    if ($data['discount_percentage'] < 0 || $data['discount_percentage'] > 100) {
                        $errors[] = 'Discount percentage must be between 0 and 100';
                    }
                }
                break;

            case 'monthly_subscription':
                if (!isset($data['subscription_price']) || $data['subscription_price'] <= 0) {
                    $errors[] = 'Subscription price is required for monthly subscription model';
                }

                if (isset($data['trial_period_days'])) {
                    if ($data['trial_period_days'] < 0 || $data['trial_period_days'] > 365) {
                        $errors[] = 'Trial period must be between 0 and 365 days';
                    }
                }
                break;

            case 'full_curriculum':
                // No specific pricing validation for curriculum access
                break;

            default:
                $errors[] = 'Invalid access model specified';
        }

        return $errors;
    }

    /**
     * Get enabled access models for tenant
     */
    private function getEnabledAccessModels(Tenant $tenant): array
    {
        $enabledModels = [];

        // Check tenant feature flags
        $features = $tenant->features ?? [];

        if ($features['course_one_time_purchase'] ?? true) {
            $enabledModels[] = 'one_time';
        }

        if ($features['course_monthly_subscription'] ?? false) {
            $enabledModels[] = 'monthly_subscription';
        }

        if ($features['course_full_curriculum'] ?? false) {
            $enabledModels[] = 'full_curriculum';
        }

        return $enabledModels;
    }

    /**
     * Validate access model is enabled for tenant
     */
    private function validateAccessModel(string $accessModel, Tenant $tenant): void
    {
        $enabledModels = $this->getEnabledAccessModels($tenant);

        if (!in_array($accessModel, $enabledModels)) {
            throw new \Exception("Access model '{$accessModel}' is not enabled for this tenant");
        }
    }

    /**
     * Calculate discounted price
     */
    private function calculateDiscountedPrice(?float $basePrice, ?float $discountPercentage): ?float
    {
        if (!$basePrice || !$discountPercentage) {
            return $basePrice;
        }

        return round($basePrice * (1 - $discountPercentage / 100), 2);
    }

    /**
     * Get exchange rate between currencies
     */
    private function getExchangeRate(string $fromCurrency, string $toCurrency): float
    {
        if ($fromCurrency === $toCurrency) {
            return 1.0;
        }

        $cacheKey = "exchange_rate_{$fromCurrency}_{$toCurrency}";

        return Cache::remember($cacheKey, 3600, function () use ($fromCurrency, $toCurrency) {
            // This would integrate with a real exchange rate API
            // For now, return mock rates for common currencies
            $mockRates = [
                'USD_EUR' => 0.85,
                'USD_GBP' => 0.73,
                'USD_CAD' => 1.25,
                'USD_AUD' => 1.35,
                'EUR_USD' => 1.18,
                'GBP_USD' => 1.37,
                'CAD_USD' => 0.80,
                'AUD_USD' => 0.74,
            ];

            $rateKey = "{$fromCurrency}_{$toCurrency}";
            $reverseKey = "{$toCurrency}_{$fromCurrency}";

            if (isset($mockRates[$rateKey])) {
                return $mockRates[$rateKey];
            }

            if (isset($mockRates[$reverseKey])) {
                return 1 / $mockRates[$reverseKey];
            }

            // Default to 1:1 if no rate found
            Log::warning("Exchange rate not found for {$fromCurrency} to {$toCurrency}");
            return 1.0;
        });
    }

    /**
     * Clear pricing-related caches
     */
    private function clearPricingCache(string $courseId, string $tenantId): void
    {
        Cache::forget("course_pricing_{$courseId}_{$tenantId}");
        Cache::forget("course_{$courseId}");
        Cache::forget("courses_list_{$tenantId}");
        Cache::tags(['exchange_rates'])->flush();
    }

    /**
     * Get supported access models for current tenant
     */
    public function getSupportedAccessModels(): array
    {
        $tenantId = Auth::user()->tenant_id;
        $tenant = Tenant::find($tenantId);

        return $this->getEnabledAccessModels($tenant);
    }

    /**
     * Get pricing summary for reporting
     */
    public function getPricingSummary(string $courseId): array
    {
        $pricing = $this->getCoursePricing($courseId);

        return [
            'access_model' => $pricing->accessModel,
            'has_pricing' => $pricing->basePrice > 0 || $pricing->subscriptionPrice > 0,
            'is_free' => $pricing->basePrice === 0 && $pricing->subscriptionPrice === 0,
            'has_discount' => $pricing->discountPercentage > 0,
            'has_trial' => $pricing->trialPeriodDays > 0,
            'is_active' => $pricing->isActive,
        ];
    }
}
