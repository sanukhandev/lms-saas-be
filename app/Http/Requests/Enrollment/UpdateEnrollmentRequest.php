<?php

namespace App\Http\Requests\Enrollment;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEnrollmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => 'sometimes|in:active,suspended,completed,cancelled',
            'progress' => 'sometimes|numeric|min:0|max:100',
            'grade' => 'nullable|numeric|min:0|max:100'
        ];
    }

    public function messages(): array
    {
        return [
            'status.in' => 'Status must be one of: active, suspended, completed, cancelled',
            'progress.numeric' => 'Progress must be a number',
            'progress.min' => 'Progress cannot be negative',
            'progress.max' => 'Progress cannot exceed 100%',
            'grade.numeric' => 'Grade must be a number',
            'grade.min' => 'Grade cannot be negative',
            'grade.max' => 'Grade cannot exceed 100%'
        ];
    }
}
