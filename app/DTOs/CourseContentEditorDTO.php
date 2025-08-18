<?php

namespace App\DTOs;

use App\Models\CourseContent;
use Illuminate\Support\Collection;

class CourseContentEditorDTO
{
    public function __construct(
        public readonly int $id,
        public readonly int $courseId,
        public readonly ?int $parentId,
        public readonly string $type,
        public readonly string $title,
        public readonly ?string $description,
        public readonly ?string $content,
        public readonly ?array $contentData,
        public readonly ?string $videoUrl,
        public readonly ?string $filePath,
        public readonly ?string $fileUrl,
        public readonly ?string $fileType,
        public readonly ?int $fileSize,
        public readonly ?string $formattedFileSize,
        public readonly ?array $learningObjectives,
        public readonly string $status,
        public readonly bool $isRequired,
        public readonly bool $isFree,
        public readonly int $position,
        public readonly int $sortOrder,
        public readonly ?int $durationMins,
        public readonly ?int $estimatedDuration,
        public readonly ?string $formattedDuration,
        public readonly ?\DateTime $publishedAt,
        public readonly string $contentTypeIcon,
        public readonly int $hierarchyLevel,
        public readonly ?\DateTime $createdAt,
        public readonly ?\DateTime $updatedAt,
        public readonly ?Collection $children = null,
        public readonly ?array $parent = null,
    ) {}

    public static function fromModel(CourseContent $content): self
    {
        return new self(
            id: $content->id,
            courseId: $content->course_id,
            parentId: $content->parent_id,
            type: $content->type,
            title: $content->title,
            description: $content->description,
            content: $content->content,
            contentData: $content->content_data,
            videoUrl: $content->video_url,
            filePath: $content->file_path,
            fileUrl: $content->file_url,
            fileType: $content->file_type,
            fileSize: $content->file_size,
            formattedFileSize: $content->formatted_file_size,
            learningObjectives: $content->learning_objectives,
            status: $content->status,
            isRequired: $content->is_required,
            isFree: $content->is_free,
            position: $content->position,
            sortOrder: $content->sort_order,
            durationMins: $content->duration_mins,
            estimatedDuration: $content->estimated_duration,
            formattedDuration: $content->formatted_duration,
            publishedAt: $content->published_at,
            contentTypeIcon: $content->getContentTypeIcon(),
            hierarchyLevel: $content->getHierarchyLevel(),
            createdAt: $content->created_at,
            updatedAt: $content->updated_at,
            children: $content->relationLoaded('children')
                ? $content->children->map(fn($child) => self::fromModel($child))
                : null,
            parent: $content->relationLoaded('parent') && $content->parent
                ? [
                    'id' => $content->parent->id,
                    'title' => $content->parent->title,
                    'type' => $content->parent->type,
                ]
                : null,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'course_id' => $this->courseId,
            'parent_id' => $this->parentId,
            'type' => $this->type,
            'title' => $this->title,
            'description' => $this->description,
            'content' => $this->content,
            'content_data' => $this->contentData,
            'video_url' => $this->videoUrl,
            'file_path' => $this->filePath,
            'file_url' => $this->fileUrl,
            'file_type' => $this->fileType,
            'file_size' => $this->fileSize,
            'formatted_file_size' => $this->formattedFileSize,
            'learning_objectives' => $this->learningObjectives,
            'status' => $this->status,
            'is_required' => $this->isRequired,
            'is_free' => $this->isFree,
            'position' => $this->position,
            'sort_order' => $this->sortOrder,
            'duration_mins' => $this->durationMins,
            'estimated_duration' => $this->estimatedDuration,
            'formatted_duration' => $this->formattedDuration,
            'published_at' => $this->publishedAt?->format('Y-m-d H:i:s'),
            'content_type_icon' => $this->contentTypeIcon,
            'hierarchy_level' => $this->hierarchyLevel,
            'created_at' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s'),
            'children' => $this->children?->map(fn($child) => $child->toArray())->toArray(),
            'parent' => $this->parent,
        ];
    }
}
