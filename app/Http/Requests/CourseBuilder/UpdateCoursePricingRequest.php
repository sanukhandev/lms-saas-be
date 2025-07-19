<?php

namespace App\Http\Requests\CourseBuilder;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCoursePricingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'access_model' => ['required', Rule::in(['one_time', 'subscription', 'full_term'])],
            'base_price' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'subscription_price' => ['nullable', 'numeric', 'min:0'],
            'subscription_period' => ['nullable', Rule::in(['monthly', 'quarterly', 'yearly'])],
            'full_term_price' => ['nullable', 'numeric', 'min:0'],
            'full_term_duration_months' => ['nullable', 'integer', 'min:1', 'max:60'],
            'discount_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'discount_valid_until' => ['nullable', 'date', 'after:today'],
            'is_free' => ['boolean'],
            'trial_period_days' => ['nullable', 'integer', 'min:0', 'max:90'],
        ];
    }

    public function messages(): array
    {
        return [
            'access_model.required' => 'Access model is required',
            'access_model.in' => 'Access model must be one of: one_time, subscription, full_term',
            'base_price.required' => 'Base price is required',
            'base_price.min' => 'Base price cannot be negative',
            'currency.required' => 'Currency is required',
            'currency.size' => 'Currency must be a 3-character code',
            'subscription_price.min' => 'Subscription price cannot be negative',
            'subscription_period.in' => 'Subscription period must be monthly, quarterly, or yearly',
            'full_term_price.min' => 'Full term price cannot be negative',
            'full_term_duration_months.min' => 'Full term duration must be at least 1 month',
            'full_term_duration_months.max' => 'Full term duration cannot exceed 60 months',
            'discount_percentage.min' => 'Discount percentage cannot be negative',
            'discount_percentage.max' => 'Discount percentage cannot exceed 100%',
            'discount_valid_until.after' => 'Discount must be valid from tomorrow onwards',
            'trial_period_days.min' => 'Trial period cannot be negative',
            'trial_period_days.max' => 'Trial period cannot exceed 90 days',
        ];
    }
}
