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
            'shortDescription' => $this->shortDescription,
            'slug' => $this->slug,
            'categoryId' => $this->categoryId,
            'categoryName' => $this->categoryName,
            'instructorId' => $this->instructorId,
            'instructorName' => $this->instructorName,
            'price' => $this->price,
            'currency' => $this->currency,
            'level' => $this->level,
            'durationHours' => $this->durationHours,
            'status' => $this->status,
            'isActive' => $this->isActive,
            'thumbnailUrl' => $this->thumbnailUrl,
            'previewVideoUrl' => $this->previewVideoUrl,
            'requirements' => $this->requirements,
            'whatYouWillLearn' => $this->whatYouWillLearn,
            'metaDescription' => $this->metaDescription,
            'tags' => $this->tags,
            'averageRating' => $this->averageRating,
            'enrollmentCount' => $this->enrollmentCount,
            'completionRate' => $this->completionRate,
            'contentCount' => $this->contentCount,
            'createdAt' => $this->createdAt->toISOString(),
            'updatedAt' => $this->updatedAt->toISOString(),
        ];
    }
}
