<?php

namespace App\Http\Requests\CourseContent;

use Illuminate\Foundation\Http\FormRequest;

class StoreCourseContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:video,document,quiz,assignment,text,audio,image',
            'content_url' => 'nullable|string|max:500',
            'content_file' => 'nullable|file|max:51200', // 50MB
            'content_data' => 'nullable|array',
            'order_index' => 'nullable|integer|min:0',
            'status' => 'required|in:draft,published,archived',
            'duration_minutes' => 'nullable|integer|min:0',
            'is_required' => 'boolean'
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Content title is required',
            'title.max' => 'Content title cannot exceed 255 characters',
            'type.required' => 'Content type is required',
            'type.in' => 'Content type must be one of: video, document, quiz, assignment, text, audio, image',
            'content_file.max' => 'File size cannot exceed 50MB',
            'status.required' => 'Content status is required',
            'status.in' => 'Content status must be draft, published, or archived',
            'duration_minutes.min' => 'Duration must be a positive number',
            'order_index.min' => 'Order index must be a positive number'
        ];
    }
}
