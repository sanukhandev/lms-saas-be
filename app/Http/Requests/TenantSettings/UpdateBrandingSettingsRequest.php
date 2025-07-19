<?php

namespace App\Http\Requests\TenantSettings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBrandingSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'logo_file' => 'nullable|image|mimes:png,jpg,jpeg,svg|max:2048',
            'favicon_file' => 'nullable|image|mimes:png,jpg,jpeg,ico|max:512',
            'brand_name' => 'required|string|max:255',
            'brand_tagline' => 'nullable|string|max:255',
            'brand_description' => 'nullable|string|max:1000',
            'contact_email' => 'nullable|email|max:255',
            'contact_phone' => 'nullable|string|max:20',
            'contact_address' => 'nullable|string|max:500',
            'social_links' => 'nullable|array',
            'social_links.*.platform' => 'required|string|in:facebook,twitter,linkedin,instagram,youtube',
            'social_links.*.url' => 'required|url|max:255'
        ];
    }

    public function messages(): array
    {
        return [
            'logo_file.image' => 'Logo must be an image file',
            'logo_file.mimes' => 'Logo must be a PNG, JPG, JPEG, or SVG file',
            'logo_file.max' => 'Logo file size cannot exceed 2MB',
            'favicon_file.image' => 'Favicon must be an image file',
            'favicon_file.mimes' => 'Favicon must be a PNG, JPG, JPEG, or ICO file',
            'favicon_file.max' => 'Favicon file size cannot exceed 512KB',
            'brand_name.required' => 'Brand name is required',
            'brand_name.max' => 'Brand name cannot exceed 255 characters',
            'contact_email.email' => 'Please provide a valid email address',
            'social_links.*.platform.in' => 'Social platform must be one of: facebook, twitter, linkedin, instagram, youtube',
            'social_links.*.url.url' => 'Please provide a valid URL for social links'
        ];
    }
}
