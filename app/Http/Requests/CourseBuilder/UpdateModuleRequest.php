<?php

namespace App\Http\Requests\CourseBuilder;

use Illuminate\Foundation\Http\FormRequest;

class UpdateModuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'duration_hours' => ['nullable', 'integer', 'min:1', 'max:100'],
            'is_free' => ['sometimes', 'boolean'],
            'position' => ['sometimes', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.max' => 'Module title cannot exceed 255 characters',
            'description.max' => 'Description cannot exceed 1000 characters',
            'duration_hours.min' => 'Duration must be at least 1 hour',
            'duration_hours.max' => 'Duration cannot exceed 100 hours',
            'position.min' => 'Position cannot be negative',
        ];
    }
}
