<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('user');
        
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => [
                'sometimes', 
                'string', 
                'email', 
                'max:255',
                Rule::unique('users')->where(function ($query) use ($userId) {
                    return $query->where('tenant_id', auth()->user()->tenant_id)
                                ->where('id', '!=', $userId);
                })
            ],
            'password' => ['sometimes', 'string', 'min:8', 'confirmed'],
            'role' => ['sometimes', 'string', Rule::in(['admin', 'instructor', 'student'])],
            'status' => ['sometimes', 'string', Rule::in(['active', 'inactive', 'suspended'])],
            'email_verified' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.string' => 'Name must be a string',
            'email.email' => 'Please provide a valid email address',
            'email.unique' => 'This email is already registered in your organization',
            'password.min' => 'Password must be at least 8 characters',
            'password.confirmed' => 'Password confirmation does not match',
            'role.in' => 'Role must be one of: admin, instructor, student',
            'status.in' => 'Status must be one of: active, inactive, suspended',
        ];
    }
}
