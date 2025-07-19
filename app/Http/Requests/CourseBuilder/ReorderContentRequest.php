<?php

namespace App\Http\Requests\CourseBuilder;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReorderContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'integer', 'exists:course_contents,id'],
            'items.*.position' => ['required', 'integer', 'min:0'],
            'items.*.parent_id' => ['nullable', 'integer', 'exists:course_contents,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Items array is required',
            'items.array' => 'Items must be an array',
            'items.min' => 'At least one item is required',
            'items.*.id.required' => 'Item ID is required',
            'items.*.id.exists' => 'Item does not exist',
            'items.*.position.required' => 'Position is required',
            'items.*.position.min' => 'Position cannot be negative',
            'items.*.parent_id.exists' => 'Parent item does not exist',
        ];
    }
}
