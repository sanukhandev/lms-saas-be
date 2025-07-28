<?php

namespace App\DTOs\Session;

use Illuminate\Support\Collection;

class SessionDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $courseId,
        public readonly ?string $contentId,
        public readonly string $tutorId,
        public readonly ?string $tutorName,
        public readonly ?string $contentTitle,
        public readonly string $scheduledAt,
        public readonly ?string $startedAt,
        public readonly ?string $endedAt,
        public readonly int $durationMins,
        public readonly ?string $meetingUrl,
        public readonly bool $isRecorded,
        public readonly ?string $recordingUrl,
        public readonly string $status,
        public readonly ?string $summary,
        public readonly ?string $homeworkAssigned,
        public readonly ?float $feedbackRating,
        public readonly ?string $feedbackComments,
        public readonly int $totalStudents,
        public readonly int $presentCount,
        public readonly int $absentCount,
        public readonly int $lateCount,
        public readonly Collection $attendances,
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
            'startedAt' => $this->startedAt,
            'endedAt' => $this->endedAt,
            'durationMins' => $this->durationMins,
            'meetingUrl' => $this->meetingUrl,
            'isRecorded' => $this->isRecorded,
            'recordingUrl' => $this->recordingUrl,
            'status' => $this->status,
            'summary' => $this->summary,
            'homeworkAssigned' => $this->homeworkAssigned,
            'feedbackRating' => $this->feedbackRating,
            'feedbackComments' => $this->feedbackComments,
            'totalStudents' => $this->totalStudents,
            'presentCount' => $this->presentCount,
            'absentCount' => $this->absentCount,
            'lateCount' => $this->lateCount,
            'attendances' => $this->attendances->map(fn($attendance) => $attendance->toArray())->toArray(),
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt,
        ];
    }
}
