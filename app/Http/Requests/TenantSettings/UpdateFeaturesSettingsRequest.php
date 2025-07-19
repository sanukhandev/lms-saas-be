<?php

namespace App\Http\Requests\TenantSettings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFeaturesSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'enable_course_creation' => 'boolean',
            'enable_user_registration' => 'boolean',
            'enable_course_reviews' => 'boolean',
            'enable_discussions' => 'boolean',
            'enable_certificates' => 'boolean',
            'enable_analytics' => 'boolean',
            'enable_notifications' => 'boolean',
            'enable_file_uploads' => 'boolean',
            'enable_video_streaming' => 'boolean',
            'enable_live_sessions' => 'boolean',
            'max_file_size' => 'integer|min:1|max:100',
            'allowed_file_types' => 'array',
            'allowed_file_types.*' => 'string|in:pdf,doc,docx,ppt,pptx,xls,xlsx,mp4,mp3,avi,mov,jpg,jpeg,png,gif,svg,zip,rar'
        ];
    }

    public function messages(): array
    {
        return [
            'max_file_size.min' => 'Maximum file size must be at least 1 MB',
            'max_file_size.max' => 'Maximum file size cannot exceed 100 MB',
            'allowed_file_types.*.in' => 'Invalid file type. Allowed types: pdf, doc, docx, ppt, pptx, xls, xlsx, mp4, mp3, avi, mov, jpg, jpeg, png, gif, svg, zip, rar'
        ];
    }
}
