<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Course;

class CreateCourseHierarchyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Add proper authorization logic
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'short_description' => 'nullable|string|max:500',
            'content_type' => ['required', Rule::in(['course', 'module', 'chapter', 'class'])],
            'parent_id' => 'nullable|exists:courses,id',
            'position' => 'nullable|integer|min:0',
            'content' => 'nullable|string',
            'learning_objectives' => 'nullable|array',
            'learning_objectives.*' => 'string',
            'video_url' => 'nullable|url',
            'duration_minutes' => 'nullable|numeric|min:0',

            // Course-specific fields (only for content_type = 'course')
            'category_id' => 'required_if:content_type,course|exists:categories,id',
            'instructor_id' => 'nullable|exists:users,id',
            'schedule_level' => ['nullable', Rule::in(['course', 'module', 'chapter'])],
            'status' => ['nullable', Rule::in(['draft', 'published', 'archived'])],
            'access_model' => ['nullable', Rule::in(['free', 'paid', 'subscription'])],
            'level' => ['nullable', Rule::in(['beginner', 'intermediate', 'advanced'])],
            'price' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|size:3',
            'thumbnail_url' => 'nullable|url',
            'preview_video_url' => 'nullable|url',
            'requirements' => 'nullable|array',
            'what_you_will_learn' => 'nullable|array',
            'tags' => 'nullable|array',
        ];

        // Add conditional validation based on content_type and parent
        if ($this->input('parent_id')) {
            $rules['parent_id'] = [
                'required',
                'exists:courses,id',
                function ($attribute, $value, $fail) {
                    $parent = Course::find($value);
                    if ($parent && !$parent->canHaveChildType($this->input('content_type'))) {
                        $fail("A {$parent->content_type} cannot have {$this->input('content_type')} children.");
                    }
                },
            ];
        }

        return $rules;
    }

    /**
     * Get custom error messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'title.required' => 'The title field is required.',
            'content_type.required' => 'The content type field is required.',
            'content_type.in' => 'The content type must be one of: course, module, chapter, class.',
            'parent_id.exists' => 'The selected parent does not exist.',
            'category_id.required_if' => 'The category field is required for courses.',
            'video_url.url' => 'The video URL must be a valid URL.',
            'duration_minutes.numeric' => 'The duration must be a number.',
            'duration_minutes.min' => 'The duration must be at least 0 minutes.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Require category_id only for root-level courses (no parent)
            if ($this->input('content_type') === 'course' && !$this->input('parent_id')) {
                if (!$this->filled('category_id')) {
                    $validator->errors()->add('category_id', 'The category field is required for root-level courses.');
                }
            }

            // Ensure certain course-level fields are only provided for courses
            if ($this->input('content_type') !== 'course') {
                $courseOnlyFields = ['instructor_id', 'schedule_level', 'access_model', 'price', 'currency'];
                foreach ($courseOnlyFields as $field) {
                    if ($this->filled($field)) {
                        $validator->errors()->add($field, "The {$field} field is only allowed for courses.");
                    }
                }
            }

            // Validate hierarchy depth (max 4 levels: Course -> Module -> Chapter -> Class)
            if ($this->input('parent_id')) {
                $parent = Course::find($this->input('parent_id'));
                if ($parent) {
                    $depth = $this->calculateHierarchyDepth($parent) + 1;
                    if ($depth > 4) {
                        $validator->errors()->add('parent_id', 'Maximum hierarchy depth of 4 levels exceeded.');
                    }
                }
            }
        });
    }

    /**
     * Calculate the depth of the hierarchy from root
     */
    private function calculateHierarchyDepth(Course $course): int
    {
        $depth = 1;
        $current = $course;

        while ($current->parent) {
            $depth++;
            $current = $current->parent;
        }

        return $depth;
    }
}
