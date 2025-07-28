<?php

namespace App\Http\Requests\Session;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'scheduled_at' => ['sometimes', 'date'],
            'duration_mins' => ['sometimes', 'integer', 'min:15', 'max:480'],
            'meeting_url' => ['sometimes', 'nullable', 'url'],
            'is_recorded' => ['sometimes', 'boolean'],
            'recording_url' => ['sometimes', 'nullable', 'url'],
            'status' => ['sometimes', 'string', Rule::in(['scheduled', 'live', 'completed', 'cancelled'])],
            'notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'actual_start_time' => ['sometimes', 'nullable', 'date'],
            'actual_end_time' => ['sometimes', 'nullable', 'date', 'after:actual_start_time'],
            'cancellation_reason' => ['sometimes', 'nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'scheduled_at.date' => 'Invalid schedule date format.',
            'duration_mins.min' => 'Session duration must be at least 15 minutes.',
            'duration_mins.max' => 'Session duration cannot exceed 8 hours.',
            'meeting_url.url' => 'Meeting URL must be a valid URL.',
            'recording_url.url' => 'Recording URL must be a valid URL.',
            'actual_end_time.after' => 'Session end time must be after start time.',
        ];
    }
}
