<?php

namespace App\DTOs\Course;

class CourseStatsDTO
{
    public function __construct(
        public readonly int $totalCourses,
        public readonly int $activeCourses,
        public readonly int $publishedCourses,
        public readonly int $draftCourses,
        public readonly int $totalEnrollments,
        public readonly float $avgRating,
        public readonly float $totalRevenue,
        public readonly array $popularCourses,
        public readonly array $coursesByCategory,
    ) {}

    public function toArray(): array
    {
        return [
            'total_courses' => $this->totalCourses,
            'active_courses' => $this->activeCourses,
            'published_courses' => $this->publishedCourses,
            'draft_courses' => $this->draftCourses,
            'total_enrollments' => $this->totalEnrollments,
            'avg_rating' => $this->avgRating,
            'total_revenue' => $this->totalRevenue,
            'popular_courses' => $this->popularCourses,
            'courses_by_category' => $this->coursesByCategory,
        ];
    }
}
