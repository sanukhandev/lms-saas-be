<?php

namespace App\DTOs\Enrollment;

class EnrollmentDTO
{
    public function __construct(
        public readonly int $id,
        public readonly int $userId,
        public readonly int $courseId,
        public readonly string $status,
        public readonly float $progress,
        public readonly ?float $grade,
        public readonly ?\DateTime $enrolledAt,
        public readonly ?\DateTime $completedAt,
        public readonly string $studentName,
        public readonly string $studentEmail,
        public readonly string $courseTitle,
        public readonly string $courseSlug
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->userId,
            'course_id' => $this->courseId,
            'status' => $this->status,
            'progress' => $this->progress,
            'grade' => $this->grade,
            'enrolled_at' => $this->enrolledAt?->format('Y-m-d H:i:s'),
            'completed_at' => $this->completedAt?->format('Y-m-d H:i:s'),
            'student' => [
                'name' => $this->studentName,
                'email' => $this->studentEmail
            ],
            'course' => [
                'title' => $this->courseTitle,
                'slug' => $this->courseSlug
            ]
        ];
    }
}
