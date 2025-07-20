<?php

namespace App\DTOs\Course;

class CourseStatsDTO
{
    public function __construct(
        public readonly int $totalCourses,
        public readonly int $publishedCourses,
        public readonly int $draftCourses,
        public readonly int $totalActiveStudents,
        public readonly float $averageCompletionRate,
        public readonly array $topPerformingCourses,
    ) {}

    public function toArray(): array
    {
        return [
            'total_courses' => $this->totalCourses,
            'published_courses' => $this->publishedCourses,
            'draft_courses' => $this->draftCourses,
            'total_active_students' => $this->totalActiveStudents,
            'average_completion_rate' => $this->averageCompletionRate,
            'top_performing_courses' => $this->topPerformingCourses,
        ];
    }
}
