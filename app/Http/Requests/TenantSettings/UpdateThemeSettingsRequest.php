<?php

namespace App\Http\Requests\TenantSettings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateThemeSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'primary_color' => 'required|regex:/^#([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/',
            'secondary_color' => 'required|regex:/^#([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/',
            'accent_color' => 'required|regex:/^#([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/',
            'background_color' => 'required|regex:/^#([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/',
            'text_color' => 'required|regex:/^#([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/',
            'font_family' => 'required|string|in:Inter,Roboto,Open Sans,Lato,Poppins,Montserrat,Source Sans Pro,Arial,Helvetica',
            'font_size' => 'required|string|in:small,medium,large',
            'border_radius' => 'required|string|in:none,small,medium,large,full',
            'theme_mode' => 'required|string|in:light,dark,auto',
            'custom_css' => 'nullable|string|max:10000'
        ];
    }

    public function messages(): array
    {
        return [
            'primary_color.required' => 'Primary color is required',
            'primary_color.regex' => 'Primary color must be a valid hex color (e.g., #3b82f6)',
            'secondary_color.required' => 'Secondary color is required',
            'secondary_color.regex' => 'Secondary color must be a valid hex color (e.g., #64748b)',
            'accent_color.required' => 'Accent color is required',
            'accent_color.regex' => 'Accent color must be a valid hex color (e.g., #f59e0b)',
            'background_color.required' => 'Background color is required',
            'background_color.regex' => 'Background color must be a valid hex color (e.g., #ffffff)',
            'text_color.required' => 'Text color is required',
            'text_color.regex' => 'Text color must be a valid hex color (e.g., #1f2937)',
            'font_family.in' => 'Font family must be one of: Inter, Roboto, Open Sans, Lato, Poppins, Montserrat, Source Sans Pro, Arial, Helvetica',
            'font_size.in' => 'Font size must be one of: small, medium, large',
            'border_radius.in' => 'Border radius must be one of: none, small, medium, large, full',
            'theme_mode.in' => 'Theme mode must be one of: light, dark, auto',
            'custom_css.max' => 'Custom CSS cannot exceed 10,000 characters'
        ];
    }
}
