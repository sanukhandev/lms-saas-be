<?php

namespace App\Http\Requests\ClassSchedule;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tutor_id' => ['sometimes', 'uuid', 'exists:users,id'],
            'scheduled_at' => ['sometimes', 'date'],
            'duration_mins' => ['sometimes', 'integer', 'min:15', 'max:480'],
            'meeting_url' => ['sometimes', 'nullable', 'url'],
            'is_recorded' => ['sometimes', 'boolean'],
            'status' => ['sometimes', 'string', Rule::in(['scheduled', 'live', 'completed', 'cancelled'])],
            'description' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'max_students' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:1000'],
            'location' => ['sometimes', 'nullable', 'string', 'max:255'],
            'recording_url' => ['sometimes', 'nullable', 'url'],
            'cancellation_reason' => ['sometimes', 'nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'tutor_id.exists' => 'The selected tutor does not exist.',
            'scheduled_at.date' => 'Invalid schedule date format.',
            'duration_mins.min' => 'Class duration must be at least 15 minutes.',
            'duration_mins.max' => 'Class duration cannot exceed 8 hours.',
            'meeting_url.url' => 'Meeting URL must be a valid URL.',
            'recording_url.url' => 'Recording URL must be a valid URL.',
        ];
    }
}
