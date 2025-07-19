<?php

namespace App\DTOs\Category;

class CategoryStatsDTO
{
    public function __construct(
        public readonly int $totalCategories,
        public readonly int $activeCategories,
        public readonly int $rootCategories,
        public readonly int $categoriesWithCourses,
        public readonly float $avgCoursesPerCategory,
        public readonly array $popularCategories,
    ) {}

    public function toArray(): array
    {
        return [
            'totalCategories' => $this->totalCategories,
            'rootCategories' => $this->rootCategories,
            'activeCourses' => $this->categoriesWithCourses, // Map to what frontend expects
            'averageCoursesPerCategory' => $this->avgCoursesPerCategory,
            // Keep original fields for backward compatibility
            'total_categories' => $this->totalCategories,
            'active_categories' => $this->activeCategories,
            'root_categories' => $this->rootCategories,
            'categories_with_courses' => $this->categoriesWithCourses,
            'avg_courses_per_category' => $this->avgCoursesPerCategory,
            'popular_categories' => $this->popularCategories,
        ];
    }
}
