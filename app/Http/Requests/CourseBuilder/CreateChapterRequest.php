<?php

namespace App\Http\Requests\CourseBuilder;

use Illuminate\Foundation\Http\FormRequest;

class CreateChapterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'content' => ['nullable', 'string'],
            'video_url' => ['nullable', 'url', 'max:500'],
            'duration_minutes' => ['nullable', 'integer', 'min:1', 'max:300'],
            'is_free' => ['boolean'],
            'position' => ['nullable', 'integer', 'min:0'],
            'learning_objectives' => ['nullable', 'array'],
            'learning_objectives.*' => ['string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Chapter title is required',
            'title.max' => 'Chapter title cannot exceed 255 characters',
            'description.max' => 'Description cannot exceed 1000 characters',
            'video_url.url' => 'Video URL must be a valid URL',
            'video_url.max' => 'Video URL cannot exceed 500 characters',
            'duration_minutes.min' => 'Duration must be at least 1 minute',
            'duration_minutes.max' => 'Duration cannot exceed 300 minutes',
            'position.min' => 'Position cannot be negative',
            'learning_objectives.*.max' => 'Learning objective cannot exceed 255 characters',
        ];
    }
}
