<?php

namespace App\DTOs\CourseBuilder;

use Carbon\Carbon;

class ModuleDTO
{
    public function __construct(
        public string $id,
        public string $title,
        public ?string $description,
        public int $position,
        public ?float $durationHours,
        public int $chaptersCount,
        public array $chapters,
        public Carbon $createdAt,
        public Carbon $updatedAt
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'position' => $this->position,
            'duration_hours' => $this->durationHours,
            'chapters_count' => $this->chaptersCount,
            'chapters' => array_map(fn($chapter) => $chapter->toArray(), $this->chapters),
            'created_at' => $this->createdAt->toISOString(),
            'updated_at' => $this->updatedAt->toISOString(),
        ];
    }
}
