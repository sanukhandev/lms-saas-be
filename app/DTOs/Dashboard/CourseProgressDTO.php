<?php

namespace App\DTOs\Dashboard;

class CourseProgressDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $title,
        public readonly int $enrollments,
        public readonly int $completions,
        public readonly float $completionRate,
        public readonly float $averageProgress,
        public readonly string $instructor,
        public readonly string $status,
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'enrollments' => $this->enrollments,
            'completions' => $this->completions,
            'completionRate' => $this->completionRate,
            'averageProgress' => $this->averageProgress,
            'instructor' => $this->instructor,
            'status' => $this->status,
        ];
    }
}
