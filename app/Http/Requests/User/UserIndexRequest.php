<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'role' => ['sometimes', 'string', Rule::in(['admin', 'instructor', 'student'])],
            'search' => ['sometimes', 'string', 'max:255'],
            'verified' => ['sometimes', 'boolean'],
            'status' => ['sometimes', 'string', Rule::in(['active', 'inactive', 'suspended'])],
            'sort_by' => ['sometimes', 'string', Rule::in(['name', 'email', 'role', 'created_at', 'updated_at'])],
            'sort_order' => ['sometimes', 'string', Rule::in(['asc', 'desc'])],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'role.in' => 'Role must be one of: admin, instructor, student',
            'status.in' => 'Status must be one of: active, inactive, suspended',
            'sort_by.in' => 'Sort by must be one of: name, email, role, created_at, updated_at',
            'sort_order.in' => 'Sort order must be either asc or desc',
            'per_page.max' => 'Per page cannot exceed 100 items',
        ];
    }
}
