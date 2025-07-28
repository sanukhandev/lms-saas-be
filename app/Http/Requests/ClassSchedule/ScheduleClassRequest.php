<?php

namespace App\Http\Requests\ClassSchedule;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ScheduleClassRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'content_id' => ['nullable', 'uuid', 'exists:course_contents,id'],
            'tutor_id' => ['required', 'uuid', 'exists:users,id'],
            'scheduled_at' => ['required', 'date', 'after:now'],
            'duration_mins' => ['required', 'integer', 'min:15', 'max:480'], // 15 mins to 8 hours
            'meeting_url' => ['nullable', 'url'],
            'is_recorded' => ['boolean'],
            'status' => ['string', Rule::in(['scheduled', 'live', 'completed', 'cancelled'])],
            'description' => ['nullable', 'string', 'max:1000'],
            'max_students' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'location' => ['nullable', 'string', 'max:255'],
            'recurring' => ['boolean'],
            'recurring_type' => ['nullable', 'string', Rule::in(['daily', 'weekly', 'monthly'])],
            'recurring_end_date' => ['nullable', 'date', 'after:scheduled_at'],
            'send_notifications' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'tutor_id.required' => 'A tutor must be assigned to the class.',
            'tutor_id.exists' => 'The selected tutor does not exist.',
            'scheduled_at.required' => 'Class schedule date and time is required.',
            'scheduled_at.after' => 'Class must be scheduled for a future date and time.',
            'duration_mins.required' => 'Class duration is required.',
            'duration_mins.min' => 'Class duration must be at least 15 minutes.',
            'duration_mins.max' => 'Class duration cannot exceed 8 hours.',
            'content_id.exists' => 'The selected content does not exist.',
            'meeting_url.url' => 'Meeting URL must be a valid URL.',
        ];
    }
}
