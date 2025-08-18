<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateCourseContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'type' => [
                'required',
                Rule::in([
                    'module',
                    'chapter',
                    'lesson',
                    'video',
                    'document',
                    'quiz',
                    'assignment',
                    'text',
                    'live_session'
                ])
            ],
            'parent_id' => 'nullable|exists:course_contents,id',
            'content' => 'nullable|string',
            'content_data' => 'nullable|array',
            'video_url' => 'nullable|url',
            'file' => 'nullable|file|max:102400', // 100MB max
            'learning_objectives' => 'nullable|array',
            'learning_objectives.*' => 'string|max:255',
            'status' => ['nullable', Rule::in(['draft', 'published', 'archived'])],
            'is_required' => 'nullable|boolean',
            'is_free' => 'nullable|boolean',
            'position' => 'nullable|integer|min:0',
            'sort_order' => 'nullable|integer|min:0',
            'duration_mins' => 'nullable|integer|min:0',
            'estimated_duration' => 'nullable|integer|min:0',
            'auto_publish' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Content title is required',
            'title.max' => 'Content title cannot exceed 255 characters',
            'type.required' => 'Content type is required',
            'type.in' => 'Invalid content type selected',
            'parent_id.exists' => 'Selected parent content does not exist',
            'video_url.url' => 'Video URL must be a valid URL',
            'file.max' => 'File size cannot exceed 100MB',
            'learning_objectives.array' => 'Learning objectives must be an array',
            'learning_objectives.*.string' => 'Each learning objective must be a string',
            'learning_objectives.*.max' => 'Each learning objective cannot exceed 255 characters',
            'status.in' => 'Invalid status selected',
            'position.min' => 'Position must be a positive number',
            'sort_order.min' => 'Sort order must be a positive number',
            'duration_mins.min' => 'Duration must be a positive number',
            'estimated_duration.min' => 'Estimated duration must be a positive number',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Convert string booleans to actual booleans
        if ($this->has('is_required')) {
            $this->merge(['is_required' => filter_var($this->is_required, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)]);
        }

        if ($this->has('is_free')) {
            $this->merge(['is_free' => filter_var($this->is_free, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)]);
        }

        if ($this->has('auto_publish')) {
            $this->merge(['auto_publish' => filter_var($this->auto_publish, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)]);
        }

        // Set default status if not provided
        if (!$this->has('status')) {
            $this->merge(['status' => 'draft']);
        }
    }
}
