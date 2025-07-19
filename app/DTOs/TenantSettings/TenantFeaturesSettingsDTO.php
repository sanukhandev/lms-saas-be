<?php

namespace App\DTOs\TenantSettings;

class TenantFeaturesSettingsDTO
{
    public function __construct(
        public readonly bool $enableCourseCreation,
        public readonly bool $enableUserRegistration,
        public readonly bool $enableCourseReviews,
        public readonly bool $enableDiscussions,
        public readonly bool $enableCertificates,
        public readonly bool $enableAnalytics,
        public readonly bool $enableNotifications,
        public readonly bool $enableFileUploads,
        public readonly bool $enableVideoStreaming,
        public readonly bool $enableLiveSessions,
        public readonly int $maxFileSize,
        public readonly array $allowedFileTypes
    ) {}

    public function toArray(): array
    {
        return [
            'enable_course_creation' => $this->enableCourseCreation,
            'enable_user_registration' => $this->enableUserRegistration,
            'enable_course_reviews' => $this->enableCourseReviews,
            'enable_discussions' => $this->enableDiscussions,
            'enable_certificates' => $this->enableCertificates,
            'enable_analytics' => $this->enableAnalytics,
            'enable_notifications' => $this->enableNotifications,
            'enable_file_uploads' => $this->enableFileUploads,
            'enable_video_streaming' => $this->enableVideoStreaming,
            'enable_live_sessions' => $this->enableLiveSessions,
            'max_file_size' => $this->maxFileSize,
            'allowed_file_types' => $this->allowedFileTypes
        ];
    }
}
