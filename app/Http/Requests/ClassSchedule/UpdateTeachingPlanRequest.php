<?php

namespace App\Http\Requests\ClassSchedule;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTeachingPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'content_id' => ['sometimes', 'nullable', 'uuid', 'exists:course_contents,id'],
            'instructor_id' => ['sometimes', 'uuid', 'exists:users,id'],
            'class_type' => ['sometimes', 'string', Rule::in([
                'lecture', 'workshop', 'practical', 'lab', 'seminar', 
                'discussion', 'presentation', 'assessment', 'review'
            ])],
            'planned_date' => ['sometimes', 'date'],
            'duration_mins' => ['sometimes', 'integer', 'min:15', 'max:480'],
            'learning_objectives' => ['sometimes', 'nullable', 'array'],
            'learning_objectives.*' => ['string', 'max:255'],
            'prerequisites' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'materials_needed' => ['sometimes', 'nullable', 'array'],
            'materials_needed.*' => ['string', 'max:255'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'priority' => ['sometimes', 'integer', 'min:1', 'max:5'],
            'is_flexible' => ['sometimes', 'boolean'],
            'status' => ['sometimes', 'string', Rule::in(['planned', 'scheduled', 'completed', 'cancelled'])],
        ];
    }

    public function messages(): array
    {
        return [
            'instructor_id.exists' => 'The selected instructor does not exist.',
            'class_type.in' => 'Invalid class type selected.',
            'planned_date.date' => 'Invalid planned date format.',
            'duration_mins.min' => 'Duration must be at least 15 minutes.',
            'duration_mins.max' => 'Duration cannot exceed 8 hours.',
            'priority.min' => 'Priority must be between 1 and 5.',
            'priority.max' => 'Priority must be between 1 and 5.',
        ];
    }
}
