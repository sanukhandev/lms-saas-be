<?php

namespace App\Http\Requests\TenantSettings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSecuritySettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'require_email_verification' => 'boolean',
            'enable_two_factor' => 'boolean',
            'password_min_length' => 'integer|min:6|max:20',
            'password_require_uppercase' => 'boolean',
            'password_require_lowercase' => 'boolean',
            'password_require_numbers' => 'boolean',
            'password_require_symbols' => 'boolean',
            'session_timeout' => 'integer|min:30|max:1440',
            'max_login_attempts' => 'integer|min:3|max:10',
            'lockout_duration' => 'integer|min:5|max:60',
            'allowed_domains' => 'nullable|array',
            'allowed_domains.*' => 'string|regex:/^[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/',
            'blocked_domains' => 'nullable|array',
            'blocked_domains.*' => 'string|regex:/^[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/'
        ];
    }

    public function messages(): array
    {
        return [
            'password_min_length.min' => 'Minimum password length must be at least 6 characters',
            'password_min_length.max' => 'Minimum password length cannot exceed 20 characters',
            'session_timeout.min' => 'Session timeout must be at least 30 minutes',
            'session_timeout.max' => 'Session timeout cannot exceed 1440 minutes (24 hours)',
            'max_login_attempts.min' => 'Maximum login attempts must be at least 3',
            'max_login_attempts.max' => 'Maximum login attempts cannot exceed 10',
            'lockout_duration.min' => 'Lockout duration must be at least 5 minutes',
            'lockout_duration.max' => 'Lockout duration cannot exceed 60 minutes',
            'allowed_domains.*.regex' => 'Please provide valid domain names for allowed domains',
            'blocked_domains.*.regex' => 'Please provide valid domain names for blocked domains'
        ];
    }
}
