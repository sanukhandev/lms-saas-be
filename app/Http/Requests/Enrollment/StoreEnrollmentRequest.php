<?php

namespace App\Http\Requests\Enrollment;

use Illuminate\Foundation\Http\FormRequest;

class StoreEnrollmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'course_id' => 'required|integer|exists:courses,id',
            'user_id' => 'required|integer|exists:users,id',
            'status' => 'sometimes|in:active,suspended,completed,cancelled'
        ];
    }

    public function messages(): array
    {
        return [
            'course_id.required' => 'Course ID is required',
            'course_id.exists' => 'Selected course does not exist',
            'user_id.required' => 'User ID is required',
            'user_id.exists' => 'Selected user does not exist',
            'status.in' => 'Status must be one of: active, suspended, completed, cancelled'
        ];
    }
}
