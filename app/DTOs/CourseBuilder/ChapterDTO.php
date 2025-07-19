<?php

namespace App\DTOs\CourseBuilder;

use Carbon\Carbon;

class ChapterDTO
{
    public function __construct(
        public string $id,
        public string $moduleId,
        public string $title,
        public ?string $description,
        public int $position,
        public ?int $durationMinutes,
        public ?string $videoUrl,
        public ?string $content,
        public array $learningObjectives,
        public bool $isCompleted,
        public Carbon $createdAt,
        public Carbon $updatedAt
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'module_id' => $this->moduleId,
            'title' => $this->title,
            'description' => $this->description,
            'position' => $this->position,
            'duration_minutes' => $this->durationMinutes,
            'video_url' => $this->videoUrl,
            'content' => $this->content,
            'learning_objectives' => $this->learningObjectives,
            'is_completed' => $this->isCompleted,
            'created_at' => $this->createdAt->toISOString(),
            'updated_at' => $this->updatedAt->toISOString(),
        ];
    }
}
