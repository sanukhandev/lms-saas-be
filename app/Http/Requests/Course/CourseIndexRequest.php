<?php

namespace App\Http\Requests\Course;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CourseIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => ['sometimes', 'nullable', 'uuid', 'exists:categories,id'],
            'instructor_id' => ['sometimes', 'nullable', 'uuid', 'exists:users,id'],
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
            'content_type' => ['sometimes', 'nullable', 'string', Rule::in(['course', 'module', 'chapter', 'class'])],
            'is_active' => ['sometimes', 'boolean'],
            'status' => ['sometimes', 'string', Rule::in(['draft', 'published', 'archived'])],
            'level' => ['sometimes', 'string', Rule::in(['beginner', 'intermediate', 'advanced'])],
            'price_min' => ['sometimes', 'numeric', 'min:0'],
            'price_max' => ['sometimes', 'numeric', 'min:0'],
            'sort_by' => ['sometimes', 'string', Rule::in(['title', 'price', 'created_at', 'updated_at', 'enrollment_count'])],
            'sort_order' => ['sometimes', 'string', Rule::in(['asc', 'desc'])],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'category_id.exists' => 'The selected category does not exist',
            'instructor_id.exists' => 'The selected instructor does not exist',
            'status.in' => 'Status must be one of: draft, published, archived',
            'level.in' => 'Level must be one of: beginner, intermediate, advanced',
            'sort_by.in' => 'Sort by must be one of: title, price, created_at, updated_at, enrollment_count',
            'sort_order.in' => 'Sort order must be either asc or desc',
            'per_page.max' => 'Per page cannot exceed 100 items',
        ];
    }
}
