<?php

namespace App\Http\Requests\Course;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'short_description' => ['sometimes', 'nullable', 'string', 'max:500'],
            'slug' => [
                'sometimes', 
                'string', 
                'max:255',
                'regex:/^[a-z0-9-]+$/',
                Rule::unique('courses')->where(function ($query) {
                    return $query->where('tenant_id', auth()->user()->tenant_id);
                })
            ],
            'category_id' => ['sometimes', 'nullable', 'uuid', 'exists:categories,id'],
            'instructor_id' => ['sometimes', 'nullable', 'uuid', 'exists:users,id'],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'level' => ['sometimes', 'string', Rule::in(['beginner', 'intermediate', 'advanced'])],
            'duration_hours' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'status' => ['sometimes', 'string', Rule::in(['draft', 'published', 'archived'])],
            'is_active' => ['sometimes', 'boolean'],
            'thumbnail_url' => ['sometimes', 'nullable', 'url', 'max:500'],
            'preview_video_url' => ['sometimes', 'nullable', 'url', 'max:500'],
            'requirements' => ['sometimes', 'nullable', 'string'],
            'what_you_will_learn' => ['sometimes', 'nullable', 'string'],
            'meta_description' => ['sometimes', 'nullable', 'string', 'max:500'],
            'tags' => ['sometimes', 'nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Course title is required',
            'title.max' => 'Course title cannot exceed 255 characters',
            'short_description.max' => 'Short description cannot exceed 500 characters',
            'slug.unique' => 'This slug is already taken',
            'slug.regex' => 'Slug can only contain lowercase letters, numbers, and hyphens',
            'category_id.exists' => 'The selected category does not exist',
            'instructor_id.exists' => 'The selected instructor does not exist',
            'price.min' => 'Price must be at least 0',
            'currency.size' => 'Currency must be a 3-character code (e.g., USD)',
            'level.in' => 'Level must be one of: beginner, intermediate, advanced',
            'status.in' => 'Status must be one of: draft, published, archived',
            'thumbnail_url.url' => 'Thumbnail URL must be a valid URL',
            'preview_video_url.url' => 'Preview video URL must be a valid URL',
            'meta_description.max' => 'Meta description cannot exceed 500 characters',
        ];
    }
}
