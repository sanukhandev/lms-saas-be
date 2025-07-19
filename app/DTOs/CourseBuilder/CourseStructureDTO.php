<?php

namespace App\DTOs\CourseBuilder;

use Carbon\Carbon;

class CourseStructureDTO
{
    public function __construct(
        public string $courseId,
        public string $title,
        public ?string $description,
        public string $status,
        public bool $isActive,
        public array $modules,
        public int $totalDuration,
        public int $totalChapters,
        public Carbon $createdAt,
        public Carbon $updatedAt
    ) {}

    public function toArray(): array
    {
        return [
            'course_id' => $this->courseId,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'is_active' => $this->isActive,
            'modules' => array_map(fn($module) => $module->toArray(), $this->modules),
            'total_duration' => $this->totalDuration,
            'total_chapters' => $this->totalChapters,
            'created_at' => $this->createdAt->toISOString(),
            'updated_at' => $this->updatedAt->toISOString(),
        ];
    }
}
