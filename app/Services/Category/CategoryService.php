<?php

namespace App\Services\Category;

use App\DTOs\Category\{
    CategoryDTO,
    CategoryStatsDTO,
    CategoryTreeDTO
};
use App\Models\Category;
use App\Models\Course;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Str;

class CategoryService
{
    private const CACHE_TTL = 300; // 5 minutes
    private const STATS_CACHE_TTL = 600; // 10 minutes
    private const TREE_CACHE_TTL = 900; // 15 minutes

    /**
     * Get paginated categories list with filters
     */
    public function getCategoriesList(string $tenantId, array $filters = [], int $page = 1, int $perPage = 15): LengthAwarePaginator
    {
        $cacheKey = "categories_list_{$tenantId}_" . md5(serialize($filters) . "_{$page}_{$perPage}");
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($tenantId, $filters, $page, $perPage) {
            $query = Category::where('tenant_id', $tenantId)->with('parent');

            // Apply filters
            if (!empty($filters['parent_id'])) {
                $query->where('parent_id', $filters['parent_id']);
            } elseif (isset($filters['parent_id']) && $filters['parent_id'] === null) {
                $query->whereNull('parent_id');
            }

            if (!empty($filters['search'])) {
                $query->where(function ($q) use ($filters) {
                    $q->where('name', 'like', '%' . $filters['search'] . '%')
                      ->orWhere('description', 'like', '%' . $filters['search'] . '%');
                });
            }

            if (isset($filters['is_active'])) {
                $query->where('is_active', $filters['is_active']);
            }

            // Sorting
            $sortBy = $filters['sort_by'] ?? 'name';
            $sortOrder = $filters['sort_order'] ?? 'asc';
            
            if ($sortBy === 'courses_count') {
                $query->withCount('courses')->orderBy('courses_count', $sortOrder);
            } else {
                $query->orderBy($sortBy, $sortOrder);
            }

            // Get paginated results
            $categories = $query->paginate($perPage, ['*'], 'page', $page);

            // Transform to DTOs
            $categoryDTOs = $categories->getCollection()->map(function ($category) use ($tenantId) {
                return $this->transformCategoryToDTO($category, $tenantId);
            });

            return new LengthAwarePaginator(
                $categoryDTOs,
                $categories->total(),
                $categories->perPage(),
                $categories->currentPage(),
                ['path' => Paginator::resolveCurrentPath()]
            );
        });
    }

    /**
     * Get category by ID
     */
    public function getCategoryById(string $categoryId, string $tenantId): ?CategoryDTO
    {
        $cacheKey = "category_{$categoryId}_{$tenantId}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($categoryId, $tenantId) {
            $category = Category::with(['parent', 'children'])
                              ->where('id', $categoryId)
                              ->where('tenant_id', $tenantId)
                              ->first();

            return $category ? $this->transformCategoryToDTO($category, $tenantId) : null;
        });
    }

    /**
     * Create new category
     */
    public function createCategory(array $data, string $tenantId): CategoryDTO
    {
        $category = DB::transaction(function () use ($data, $tenantId) {
            $categoryData = [
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'slug' => $data['slug'] ?? Str::slug($data['name']),
                'parent_id' => $data['parent_id'] ?? null,
                'tenant_id' => $tenantId,
                'is_active' => $data['is_active'] ?? true,
                'sort_order' => $data['sort_order'] ?? 0,
                'image_url' => $data['image_url'] ?? null,
                'meta_description' => $data['meta_description'] ?? null,
            ];

            // Ensure slug uniqueness within tenant
            $originalSlug = $categoryData['slug'];
            $counter = 1;
            while (Category::where('tenant_id', $tenantId)->where('slug', $categoryData['slug'])->exists()) {
                $categoryData['slug'] = $originalSlug . '-' . $counter++;
            }

            return Category::create($categoryData);
        });

        // Clear relevant caches
        $this->clearCategoryCaches($tenantId);

        return $this->transformCategoryToDTO($category, $tenantId);
    }

    /**
     * Update category
     */
    public function updateCategory(string $categoryId, array $data, string $tenantId): ?CategoryDTO
    {
        $category = Category::where('id', $categoryId)->where('tenant_id', $tenantId)->first();
        
        if (!$category) {
            return null;
        }

        DB::transaction(function () use ($category, $data, $tenantId) {
            $updateData = [];

            if (isset($data['name'])) {
                $updateData['name'] = $data['name'];
                // Update slug if name changes
                if (!isset($data['slug'])) {
                    $newSlug = Str::slug($data['name']);
                    $originalSlug = $newSlug;
                    $counter = 1;
                    while (Category::where('tenant_id', $tenantId)
                                  ->where('slug', $newSlug)
                                  ->where('id', '!=', $category->id)
                                  ->exists()) {
                        $newSlug = $originalSlug . '-' . $counter++;
                    }
                    $updateData['slug'] = $newSlug;
                }
            }

            if (isset($data['description'])) {
                $updateData['description'] = $data['description'];
            }

            if (isset($data['slug'])) {
                $updateData['slug'] = $data['slug'];
            }

            if (isset($data['parent_id'])) {
                // Prevent circular reference
                if ($data['parent_id'] !== $category->id && !$this->wouldCreateCircularReference($category->id, $data['parent_id'], $tenantId)) {
                    $updateData['parent_id'] = $data['parent_id'];
                }
            }

            if (isset($data['is_active'])) {
                $updateData['is_active'] = $data['is_active'];
            }

            if (isset($data['sort_order'])) {
                $updateData['sort_order'] = $data['sort_order'];
            }

            if (isset($data['image_url'])) {
                $updateData['image_url'] = $data['image_url'];
            }

            if (isset($data['meta_description'])) {
                $updateData['meta_description'] = $data['meta_description'];
            }

            $category->update($updateData);
        });

        // Clear relevant caches
        $this->clearCategoryCaches($tenantId, $categoryId);

        return $this->transformCategoryToDTO($category->fresh(), $tenantId);
    }

    /**
     * Delete category
     */
    public function deleteCategory(string $categoryId, string $tenantId): bool
    {
        $category = Category::where('id', $categoryId)->where('tenant_id', $tenantId)->first();
        
        if (!$category) {
            return false;
        }

        // Check if category has courses
        $hasCourses = Course::where('category_id', $categoryId)->exists();
        if ($hasCourses) {
            throw new \Exception('Cannot delete category that contains courses');
        }

        // Check if category has children
        $hasChildren = Category::where('parent_id', $categoryId)->exists();
        if ($hasChildren) {
            throw new \Exception('Cannot delete category that has subcategories');
        }

        DB::transaction(function () use ($category) {
            $category->delete();
        });

        // Clear relevant caches
        $this->clearCategoryCaches($tenantId, $categoryId);

        return true;
    }

    /**
     * Get category tree
     */
    public function getCategoryTree(string $tenantId): array
    {
        $cacheKey = "category_tree_{$tenantId}";
        
        return Cache::remember($cacheKey, self::TREE_CACHE_TTL, function () use ($tenantId) {
            $categories = Category::where('tenant_id', $tenantId)
                                ->where('is_active', true)
                                ->orderBy('sort_order')
                                ->orderBy('name')
                                ->get();

            return $this->buildCategoryTree($categories);
        });
    }

    /**
     * Get category statistics
     */
    public function getCategoryStats(string $tenantId): CategoryStatsDTO
    {
        $cacheKey = "category_stats_{$tenantId}";
        
        return Cache::remember($cacheKey, self::STATS_CACHE_TTL, function () use ($tenantId) {
            $totalCategories = Category::where('tenant_id', $tenantId)->count();
            $activeCategories = Category::where('tenant_id', $tenantId)->where('is_active', true)->count();
            $rootCategories = Category::where('tenant_id', $tenantId)->whereNull('parent_id')->count();

            // Categories with courses
            $categoriesWithCourses = Category::where('tenant_id', $tenantId)
                                           ->whereHas('courses')
                                           ->count();

            // Average courses per category
            $totalCourses = Course::where('tenant_id', $tenantId)->count();
            $avgCoursesPerCategory = $totalCategories > 0 ? round($totalCourses / $totalCategories, 2) : 0;

            // Most popular categories
            $popularCategories = Category::where('tenant_id', $tenantId)
                                       ->withCount('courses')
                                       ->orderBy('courses_count', 'desc')
                                       ->limit(5)
                                       ->get()
                                       ->map(fn($cat) => [
                                           'id' => $cat->id,
                                           'name' => $cat->name,
                                           'courses_count' => $cat->courses_count
                                       ])
                                       ->toArray();

            return new CategoryStatsDTO(
                totalCategories: $totalCategories,
                activeCategories: $activeCategories,
                rootCategories: $rootCategories,
                categoriesWithCourses: $categoriesWithCourses,
                avgCoursesPerCategory: $avgCoursesPerCategory,
                popularCategories: $popularCategories
            );
        });
    }

    /**
     * Transform Category model to CategoryDTO
     */
    private function transformCategoryToDTO(Category $category, string $tenantId): CategoryDTO
    {
        // Get courses count (cached)
        $coursesCount = Cache::remember(
            "category_courses_count_{$category->id}_{$tenantId}",
            self::CACHE_TTL,
            fn() => $category->courses()->count()
        );

        // Get children count (cached)
        $childrenCount = Cache::remember(
            "category_children_count_{$category->id}_{$tenantId}",
            self::CACHE_TTL,
            fn() => $category->children()->count()
        );

        return new CategoryDTO(
            id: $category->id,
            name: $category->name,
            description: $category->description,
            slug: $category->slug,
            parentId: $category->parent_id,
            parentName: $category->parent?->name,
            isActive: $category->is_active,
            sortOrder: $category->sort_order,
            imageUrl: $category->image_url,
            metaDescription: $category->meta_description,
            coursesCount: $coursesCount,
            childrenCount: $childrenCount,
            createdAt: $category->created_at,
            updatedAt: $category->updated_at
        );
    }

    /**
     * Build category tree structure
     */
    private function buildCategoryTree(Collection $categories, ?string $parentId = null): array
    {
        $tree = [];
        
        foreach ($categories->where('parent_id', $parentId) as $category) {
            $children = $this->buildCategoryTree($categories, $category->id);
            
            $tree[] = [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'sort_order' => $category->sort_order,
                'children' => $children
            ];
        }

        return $tree;
    }

    /**
     * Check if moving a category would create circular reference
     */
    private function wouldCreateCircularReference(string $categoryId, ?string $newParentId, string $tenantId): bool
    {
        if (!$newParentId) {
            return false;
        }

        $current = Category::where('id', $newParentId)->where('tenant_id', $tenantId)->first();
        
        while ($current) {
            if ($current->id === $categoryId) {
                return true;
            }
            $current = $current->parent;
        }

        return false;
    }

    /**
     * Clear category-related caches
     */
    private function clearCategoryCaches(string $tenantId, ?string $categoryId = null): void
    {
        // Clear stats and tree caches
        Cache::forget("category_stats_{$tenantId}");
        Cache::forget("category_tree_{$tenantId}");
        
        // Clear specific category cache if provided
        if ($categoryId) {
            Cache::forget("category_{$categoryId}_{$tenantId}");
            Cache::forget("category_courses_count_{$categoryId}_{$tenantId}");
            Cache::forget("category_children_count_{$categoryId}_{$tenantId}");
        }

        // Clear list caches (pattern-based clearing)
        $keys = Cache::getRedis()->keys("*categories_list_{$tenantId}*");
        foreach ($keys as $key) {
            Cache::forget(str_replace(config('cache.prefix') . ':', '', $key));
        }
    }
}
