<?php

namespace App\Http\Requests\Session;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MarkAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'attendances' => ['required', 'array', 'min:1'],
            'attendances.*.student_id' => ['required', 'uuid', 'exists:users,id'],
            'attendances.*.attendance_status' => ['required', 'string', Rule::in([
                'present', 'absent', 'late', 'excused', 'partial'
            ])],
            'attendances.*.joined_at' => ['nullable', 'date'],
            'attendances.*.left_at' => ['nullable', 'date', 'after:attendances.*.joined_at'],
            'attendances.*.location' => ['nullable', 'string', 'max:255'],
            'attendances.*.notes' => ['nullable', 'string', 'max:500'],
            'mark_all_as' => ['nullable', 'string', Rule::in([
                'present', 'absent', 'late', 'excused', 'partial'
            ])],
        ];
    }

    public function messages(): array
    {
        return [
            'attendances.required' => 'Attendance data is required.',
            'attendances.*.student_id.required' => 'Student ID is required for each attendance record.',
            'attendances.*.student_id.exists' => 'One or more students do not exist.',
            'attendances.*.attendance_status.required' => 'Attendance status is required.',
            'attendances.*.attendance_status.in' => 'Invalid attendance status.',
            'attendances.*.left_at.after' => 'Leave time must be after join time.',
        ];
    }
}
