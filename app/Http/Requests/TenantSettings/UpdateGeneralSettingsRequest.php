<?php

namespace App\Http\Requests\TenantSettings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateGeneralSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = Auth::user()->tenant->id;
        
        return [
            'name' => 'required|string|max:255',
            'domain' => 'required|string|max:255|unique:tenants,domain,' . $tenantId,
            'description' => 'nullable|string|max:1000',
            'timezone' => 'required|string|max:50',
            'language' => 'required|string|max:10',
            'date_format' => 'required|string|max:20',
            'time_format' => 'required|string|max:20',
            'currency' => 'required|string|max:3',
            'max_users' => 'required|integer|min:1',
            'max_courses' => 'required|integer|min:1',
            'storage_limit' => 'required|integer|min:100'
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Tenant name is required',
            'name.max' => 'Tenant name cannot exceed 255 characters',
            'domain.required' => 'Domain is required',
            'domain.unique' => 'This domain is already taken',
            'description.max' => 'Description cannot exceed 1000 characters',
            'timezone.required' => 'Timezone is required',
            'language.required' => 'Language is required',
            'date_format.required' => 'Date format is required',
            'time_format.required' => 'Time format is required',
            'currency.required' => 'Currency is required',
            'max_users.required' => 'Maximum users limit is required',
            'max_users.min' => 'Maximum users must be at least 1',
            'max_courses.required' => 'Maximum courses limit is required',
            'max_courses.min' => 'Maximum courses must be at least 1',
            'storage_limit.required' => 'Storage limit is required',
            'storage_limit.min' => 'Storage limit must be at least 100 MB'
        ];
    }
}
