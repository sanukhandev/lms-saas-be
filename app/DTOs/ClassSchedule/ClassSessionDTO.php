<?php

namespace App\DTOs\ClassSchedule;

class ClassSessionDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $courseId,
        public readonly ?string $contentId,
        public readonly string $tutorId,
        public readonly ?string $tutorName,
        public readonly ?string $contentTitle,
        public readonly string $scheduledAt,
        public readonly int $durationMins,
        public readonly ?string $meetingUrl,
        public readonly bool $isRecorded,
        public readonly ?string $recordingUrl,
        public readonly string $status,
        public readonly int $studentsCount,
        public readonly string $createdAt,
        public readonly string $updatedAt
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'courseId' => $this->courseId,
            'contentId' => $this->contentId,
            'tutorId' => $this->tutorId,
            'tutorName' => $this->tutorName,
            'contentTitle' => $this->contentTitle,
            'scheduledAt' => $this->scheduledAt,
            'durationMins' => $this->durationMins,
            'meetingUrl' => $this->meetingUrl,
            'isRecorded' => $this->isRecorded,
            'recordingUrl' => $this->recordingUrl,
            'status' => $this->status,
            'studentsCount' => $this->studentsCount,
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt,
        ];
    }
}
