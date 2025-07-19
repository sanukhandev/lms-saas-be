<?php

namespace App\DTOs\Course;

use Carbon\Carbon;

class CourseDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $title,
        public readonly ?string $description,
        public readonly ?string $shortDescription,
        public readonly ?string $slug,
        public readonly ?string $categoryId,
        public readonly ?string $categoryName,
        public readonly ?string $instructorId,
        public readonly ?string $instructorName,
        public readonly ?float $price,
        public readonly ?string $currency,
        public readonly ?string $level,
        public readonly ?int $durationHours,
        public readonly ?string $status,
        public readonly bool $isActive,
        public readonly ?string $thumbnailUrl,
        public readonly ?string $previewVideoUrl,
        public readonly ?string $requirements,
        public readonly ?string $whatYouWillLearn,
        public readonly ?string $metaDescription,
        public readonly ?string $tags,
        public readonly ?float $averageRating,
        public readonly int $enrollmentCount,
        public readonly float $completionRate,
        public readonly int $contentCount,
        public readonly Carbon $createdAt,
        public readonly Carbon $updatedAt,
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'short_description' => $this->shortDescription,
            'slug' => $this->slug,
            'category_id' => $this->categoryId,
            'category_name' => $this->categoryName,
            'instructor_id' => $this->instructorId,
            'instructor_name' => $this->instructorName,
            'price' => $this->price,
            'currency' => $this->currency,
            'level' => $this->level,
            'duration_hours' => $this->durationHours,
            'status' => $this->status,
            'is_active' => $this->isActive,
            'thumbnail_url' => $this->thumbnailUrl,
            'preview_video_url' => $this->previewVideoUrl,
            'requirements' => $this->requirements,
            'what_you_will_learn' => $this->whatYouWillLearn,
            'meta_description' => $this->metaDescription,
            'tags' => $this->tags,
            'average_rating' => $this->averageRating,
            'enrollment_count' => $this->enrollmentCount,
            'completion_rate' => $this->completionRate,
            'content_count' => $this->contentCount,
            'created_at' => $this->createdAt->toISOString(),
            'updated_at' => $this->updatedAt->toISOString(),
        ];
    }
}
