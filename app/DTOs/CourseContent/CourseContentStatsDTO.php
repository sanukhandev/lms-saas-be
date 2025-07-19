<?php

namespace App\DTOs\CourseContent;

class CourseContentStatsDTO
{
    public function __construct(
        public readonly int $totalContent,
        public readonly int $publishedContent,
        public readonly int $draftContent,
        public readonly int $totalDurationMinutes,
        public readonly array $contentByType
    ) {}

    public function toArray(): array
    {
        return [
            'total_content' => $this->totalContent,
            'published_content' => $this->publishedContent,
            'draft_content' => $this->draftContent,
            'total_duration_minutes' => $this->totalDurationMinutes,
            'total_duration_hours' => round($this->totalDurationMinutes / 60, 2),
            'content_by_type' => $this->contentByType,
            'completion_percentage' => $this->totalContent > 0 
                ? round(($this->publishedContent / $this->totalContent) * 100, 2) 
                : 0
        ];
    }
}
