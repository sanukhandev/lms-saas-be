<?php

namespace App\DTOs\CourseContent;

class CourseContentDTO
{
    public function __construct(
        public readonly int $id,
        public readonly int $courseId,
        public readonly string $title,
        public readonly ?string $description,
        public readonly string $type,
        public readonly ?string $contentUrl,
        public readonly ?array $contentData,
        public readonly int $orderIndex,
        public readonly string $status,
        public readonly ?int $durationMinutes,
        public readonly bool $isRequired,
        public readonly \DateTime $createdAt,
        public readonly \DateTime $updatedAt
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'course_id' => $this->courseId,
            'title' => $this->title,
            'description' => $this->description,
            'type' => $this->type,
            'content_url' => $this->contentUrl,
            'content_data' => $this->contentData,
            'order_index' => $this->orderIndex,
            'status' => $this->status,
            'duration_minutes' => $this->durationMinutes,
            'is_required' => $this->isRequired,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt->format('Y-m-d H:i:s')
        ];
    }
}
