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
            'totalCourses' => $this->totalCourses,
            'publishedCourses' => $this->publishedCourses,
            'draftCourses' => $this->draftCourses,
            'totalActiveStudents' => $this->totalActiveStudents,
            'averageCompletionRate' => $this->averageCompletionRate,
            'topPerformingCourses' => $this->topPerformingCourses,
        ];
    }
}
