<?php

namespace App\DTOs\Category;

class CategoryTreeDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $slug,
        public readonly int $sortOrder,
        public readonly array $children,
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'sort_order' => $this->sortOrder,
            'children' => $this->children,
        ];
    }
}
