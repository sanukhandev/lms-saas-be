<?php

namespace App\Http\Requests\CourseContent;

use Illuminate\Foundation\Http\FormRequest;

class ReorderCourseContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|integer|exists:course_contents,id',
            'items.*.order_index' => 'required|integer|min:0'
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Items array is required',
            'items.array' => 'Items must be an array',
            'items.min' => 'At least one item is required',
            'items.*.id.required' => 'Each item must have an ID',
            'items.*.id.exists' => 'Content ID does not exist',
            'items.*.order_index.required' => 'Each item must have an order index',
            'items.*.order_index.min' => 'Order index must be a positive number'
        ];
    }
}
