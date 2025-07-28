<?php

namespace App\Http\Requests\ClassSchedule;

use Illuminate\Foundation\Http\FormRequest;

class BulkScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'teaching_plan_ids' => ['required', 'array', 'min:1'],
            'teaching_plan_ids.*' => ['uuid', 'exists:teaching_plans,id'],
            'start_date' => ['required', 'date', 'after:now'],
            'tutor_assignments' => ['required', 'array'],
            'tutor_assignments.*.plan_id' => ['required', 'uuid', 'exists:teaching_plans,id'],
            'tutor_assignments.*.tutor_id' => ['required', 'uuid', 'exists:users,id'],
            'tutor_assignments.*.scheduled_at' => ['required', 'date', 'after:start_date'],
            'tutor_assignments.*.meeting_url' => ['nullable', 'url'],
            'schedule_options' => ['array'],
            'schedule_options.auto_spacing_hours' => ['integer', 'min:1', 'max:168'], // 1 hour to 1 week
            'schedule_options.working_hours_start' => ['date_format:H:i'],
            'schedule_options.working_hours_end' => ['date_format:H:i'],
            'schedule_options.working_days' => ['array'],
            'schedule_options.working_days.*' => ['integer', 'min:0', 'max:6'], // 0=Sunday, 6=Saturday
            'schedule_options.buffer_time_mins' => ['integer', 'min:0', 'max:120'],
            'send_notifications' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'teaching_plan_ids.required' => 'At least one teaching plan must be selected.',
            'teaching_plan_ids.*.exists' => 'One or more selected teaching plans do not exist.',
            'start_date.required' => 'Start date is required.',
            'start_date.after' => 'Start date must be in the future.',
            'tutor_assignments.required' => 'Tutor assignments are required.',
            'tutor_assignments.*.tutor_id.required' => 'Each plan must have a tutor assigned.',
            'tutor_assignments.*.tutor_id.exists' => 'One or more selected tutors do not exist.',
            'tutor_assignments.*.scheduled_at.required' => 'Schedule time is required for each assignment.',
            'tutor_assignments.*.scheduled_at.after' => 'Schedule time must be after the start date.',
        ];
    }
}
