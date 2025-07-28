<?php

namespace App\Http\Requests\ClassSchedule;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateTeachingPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'content_id' => ['nullable', 'uuid', 'exists:course_contents,id'],
            'instructor_id' => ['required', 'uuid', 'exists:users,id'],
            'class_type' => ['required', 'string', Rule::in([
                'lecture', 'workshop', 'practical', 'lab', 'seminar', 
                'discussion', 'presentation', 'assessment', 'review'
            ])],
            'planned_date' => ['required', 'date'],
            'duration_mins' => ['required', 'integer', 'min:15', 'max:480'],
            'learning_objectives' => ['nullable', 'array'],
            'learning_objectives.*' => ['string', 'max:255'],
            'prerequisites' => ['nullable', 'string', 'max:1000'],
            'materials_needed' => ['nullable', 'array'],
            'materials_needed.*' => ['string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'priority' => ['integer', 'min:1', 'max:5'],
            'is_flexible' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'instructor_id.required' => 'An instructor must be assigned to the teaching plan.',
            'instructor_id.exists' => 'The selected instructor does not exist.',
            'class_type.required' => 'Class type is required.',
            'class_type.in' => 'Invalid class type selected.',
            'planned_date.required' => 'Planned date is required.',
            'planned_date.date' => 'Invalid planned date format.',
            'duration_mins.required' => 'Duration is required.',
            'duration_mins.min' => 'Duration must be at least 15 minutes.',
            'duration_mins.max' => 'Duration cannot exceed 8 hours.',
            'priority.min' => 'Priority must be between 1 and 5.',
            'priority.max' => 'Priority must be between 1 and 5.',
        ];
    }
}
