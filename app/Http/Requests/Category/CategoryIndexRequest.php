<?php

namespace App\Http\Requests\Category;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoryIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'parent_id' => ['sometimes', 'nullable', 'uuid', 'exists:categories,id'],
            'search' => ['sometimes', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_by' => ['sometimes', 'string', Rule::in(['name', 'created_at', 'updated_at', 'sort_order', 'courses_count'])],
            'sort_order' => ['sometimes', 'string', Rule::in(['asc', 'desc'])],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'parent_id.exists' => 'The selected parent category does not exist',
            'sort_by.in' => 'Sort by must be one of: name, created_at, updated_at, sort_order, courses_count',
            'sort_order.in' => 'Sort order must be either asc or desc',
            'per_page.max' => 'Per page cannot exceed 100 items',
        ];
    }
}
