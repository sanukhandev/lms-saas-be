<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTenantSettingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'settings' => 'required|array',
            'settings.timezone' => 'sometimes|string|timezone',
            'settings.language' => 'sometimes|string|max:5',
            'settings.theme' => 'sometimes|string|max:50',
            'settings.features' => 'sometimes|array',
            'settings.features.courses' => 'sometimes|boolean',
            'settings.features.certificates' => 'sometimes|boolean',
            'settings.features.payments' => 'sometimes|boolean',
            'settings.features.notifications' => 'sometimes|boolean',
            'settings.branding' => 'sometimes|array',
            'settings.branding.logo' => 'sometimes|string|nullable',
            'settings.branding.primary_color' => 'sometimes|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'settings.branding.secondary_color' => 'sometimes|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'settings.branding.company_name' => 'sometimes|string|max:255',
            'settings.branding.favicon' => 'sometimes|string|nullable',
            'settings.theme_config' => 'sometimes|array',
            'settings.theme_config.mode' => 'sometimes|string|in:light,dark,auto',
            'settings.theme_config.colors' => 'sometimes|array',
            'settings.theme_config.typography' => 'sometimes|array',
            'settings.theme_config.border_radius' => 'sometimes|array',
            'settings.theme_config.shadows' => 'sometimes|array',
            'settings.theme_config.spacing' => 'sometimes|array',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'settings.required' => 'Settings are required',
            'settings.timezone.timezone' => 'Invalid timezone',
            'settings.branding.primary_color.regex' => 'Primary color must be a valid hex color',
            'settings.branding.secondary_color.regex' => 'Secondary color must be a valid hex color',
            'settings.theme_config.mode.in' => 'Theme mode must be light, dark, or auto',
        ];
    }
}
