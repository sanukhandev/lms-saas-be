<?php

namespace App\DTOs\Category;

use Carbon\Carbon;

class CategoryDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly ?string $description,
        public readonly string $slug,
        public readonly ?string $parentId,
        public readonly ?string $parentName,
        public readonly bool $isActive,
        public readonly int $sortOrder,
        public readonly ?string $imageUrl,
        public readonly ?string $metaDescription,
        public readonly int $coursesCount,
        public readonly int $childrenCount,
        public readonly int $activeCoursesCount,
        public readonly int $totalStudents,
        public readonly Carbon $createdAt,
        public readonly Carbon $updatedAt,
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'slug' => $this->slug,
            'parent_id' => $this->parentId,
            'parent_name' => $this->parentName,
            'parent' => $this->parentId ? [
                'id' => $this->parentId,
                'name' => $this->parentName
            ] : null,
            'is_active' => $this->isActive,
            'sort_order' => $this->sortOrder,
            'image_url' => $this->imageUrl,
            'meta_description' => $this->metaDescription,
            'courses_count' => $this->coursesCount,
            'children_count' => $this->childrenCount,
            'subcategories_count' => $this->childrenCount, // Alias for frontend
            'active_courses_count' => $this->activeCoursesCount,
            'total_students' => $this->totalStudents,
            'created_at' => $this->createdAt->toISOString(),
            'updated_at' => $this->updatedAt->toISOString(),
            
            // Keep camelCase for backward compatibility
            'parentId' => $this->parentId,
            'parentName' => $this->parentName,
            'isActive' => $this->isActive,
            'sortOrder' => $this->sortOrder,
            'imageUrl' => $this->imageUrl,
            'metaDescription' => $this->metaDescription,
            'coursesCount' => $this->coursesCount,
            'childrenCount' => $this->childrenCount,
            'activeCoursesCount' => $this->activeCoursesCount,
            'totalStudents' => $this->totalStudents,
            'createdAt' => $this->createdAt->toISOString(),
            'updatedAt' => $this->updatedAt->toISOString(),
        ];
    }
}
