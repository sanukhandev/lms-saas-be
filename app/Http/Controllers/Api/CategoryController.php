<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    /**
     * Get statistics for categories
     */
    public function statistics(Request $request): JsonResponse
    {
        try {
            $tenantId = Auth::user()->tenant_id;
            $totalCategories = Category::where('tenant_id', $tenantId)->count();
            $rootCategories = Category::where('tenant_id', $tenantId)->whereNull('parent_id')->count();

            // Get all category IDs for this tenant
            $categoryIds = Category::where('tenant_id', $tenantId)->pluck('id');

            // Total courses in all categories
            $totalCourses = 0;
            $totalStudents = 0;
            if ($categoryIds->count() > 0) {
                $courses = Course::where('tenant_id', $tenantId)
                    ->whereIn('category_id', $categoryIds)
                    ->get();
                $totalCourses = $courses->count();
                try {
                    $totalStudents = $courses->sum(function ($course) {
                        return $course->students()->count();
                    });
                } catch (\Throwable $e) {
                    Log::error('Error counting students in statistics: ' . $e->getMessage());
                    $totalStudents = 0;
                }
            }

            $avgCoursesPerCategory = $totalCategories > 0 ? round($totalCourses / $totalCategories, 2) : 0.0;

            return response()->json([
                'success' => true,
                'data' => [
                    'totalCategories' => $totalCategories,
                    'rootCategories' => $rootCategories,
                    'totalCourses' => $totalCourses,
                    'totalStudents' => $totalStudents,
                    'avgCoursesPerCategory' => $avgCoursesPerCategory,
                ],
                'message' => 'Category statistics retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving category statistics: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving category statistics'
            ], 500);
        }
    }
    /**
     * Display a listing of categories
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Category::with(['parent', 'children', 'courses'])
                ->where('tenant_id', Auth::user()->tenant_id);

            // Filter by parent category
            if ($request->filled('parent_id')) {
                $query->where('parent_id', $request->parent_id);
            }

            // Get only root categories (no parent)
            if ($request->boolean('root_only')) {
                $query->whereNull('parent_id');
            }

            // Search functionality
            if ($request->filled('search')) {
                $query->where('name', 'like', '%' . $request->search . '%');
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'name');
            $sortOrder = $request->get('sort_order', 'asc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination or all results
            if ($request->boolean('paginate', true)) {
                $perPage = $request->get('per_page', 15);
                $categories = $query->paginate($perPage);
            } else {
                $categories = $query->get();
            }

            // Transform data to include course counts
            $transformer = function ($category) {
                $category->courses_count = $category->courses()->count();
                $category->children_count = $category->children()->count();
                return $category;
            };

            if ($request->boolean('paginate', true)) {
                $categories->getCollection()->transform($transformer);
            } else {
                $categories->transform($transformer);
            }

            return response()->json([
                'success' => true,
                'data' => $categories,
                'message' => 'Categories retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving categories: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving categories'
            ], 500);
        }
    }

    /**
     * Store a newly created category
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'parent_id' => 'nullable|exists:categories,id',
                'slug' => 'nullable|string|max:255|unique:categories,slug,NULL,id,tenant_id,' . Auth::user()->tenant_id
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Generate slug if not provided
            $slug = $request->slug ?? Str::slug($request->name);

            // Ensure slug is unique within tenant
            $originalSlug = $slug;
            $counter = 1;
            while (Category::where('tenant_id', Auth::user()->tenant_id)->where('slug', $slug)->exists()) {
                $slug = $originalSlug . '-' . $counter++;
            }

            // Validate parent category belongs to same tenant
            if ($request->parent_id) {
                $parentCategory = Category::where('id', $request->parent_id)
                    ->where('tenant_id', Auth::user()->tenant_id)
                    ->first();

                if (!$parentCategory) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Parent category not found or does not belong to your tenant'
                    ], 404);
                }
            }

            $category = Category::create([
                'tenant_id' => Auth::user()->tenant_id,
                'name' => $request->name,
                'slug' => $slug,
                'parent_id' => $request->parent_id
            ]);

            $category->load(['parent', 'children']);

            return response()->json([
                'success' => true,
                'data' => $category,
                'message' => 'Category created successfully'
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating category: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error creating category'
            ], 500);
        }
    }

    /**
     * Display the specified category
     */
    public function show(Category $category): JsonResponse
    {
        try {
            // Check if category belongs to current tenant
            if ($category->tenant_id !== Auth::user()->tenant_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Category not found'
                ], 404);
            }

            $category->load([
                'parent',
                'children',
                'courses' => function ($query) {
                    $query->with(['users' => function ($q) {
                        $q->wherePivot('role', 'student');
                    }]);
                }
            ]);

            // Add statistics
            $category->statistics = [
                'total_courses' => $category->courses()->count(),
                'active_courses' => $category->courses()->where('is_active', true)->count(),
                'total_students' => $category->courses()
                    ->with(['users' => function ($q) {
                        $q->wherePivot('role', 'student');
                    }])
                    ->get()
                    ->sum(function ($course) {
                        return $course->users->count();
                    }),
                'subcategories_count' => $category->children()->count()
            ];

            return response()->json([
                'success' => true,
                'data' => $category,
                'message' => 'Category retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving category: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving category'
            ], 500);
        }
    }

    /**
     * Update the specified category
     */
    public function update(Request $request, Category $category): JsonResponse
    {
        try {
            // Check if category belongs to current tenant
            if ($category->tenant_id !== Auth::user()->tenant_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Category not found'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|max:255',
                'parent_id' => 'nullable|exists:categories,id',
                'slug' => 'sometimes|string|max:255|unique:categories,slug,' . $category->id . ',id,tenant_id,' . Auth::user()->tenant_id
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Prevent setting self as parent
            if ($request->parent_id == $category->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Category cannot be its own parent'
                ], 422);
            }

            // Prevent circular references
            if ($request->parent_id && $this->wouldCreateCircularReference($category, $request->parent_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Moving category would create circular reference'
                ], 422);
            }

            // Validate parent category belongs to same tenant
            if ($request->parent_id) {
                $parentCategory = Category::where('id', $request->parent_id)
                    ->where('tenant_id', Auth::user()->tenant_id)
                    ->first();

                if (!$parentCategory) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Parent category not found or does not belong to your tenant'
                    ], 404);
                }
            }

            $updateData = $request->only(['name', 'parent_id']);

            // Update slug if name changed
            if ($request->has('name') && $request->name !== $category->name) {
                $slug = $request->slug ?? Str::slug($request->name);

                // Ensure slug is unique within tenant
                $originalSlug = $slug;
                $counter = 1;
                while (Category::where('tenant_id', Auth::user()->tenant_id)
                    ->where('slug', $slug)
                    ->where('id', '!=', $category->id)
                    ->exists()
                ) {
                    $slug = $originalSlug . '-' . $counter++;
                }

                $updateData['slug'] = $slug;
            }

            $category->update($updateData);
            $category->load(['parent', 'children']);

            return response()->json([
                'success' => true,
                'data' => $category,
                'message' => 'Category updated successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating category: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error updating category'
            ], 500);
        }
    }

    /**
     * Remove the specified category
     */
    public function destroy(Category $category): JsonResponse
    {
        try {
            // Check if category belongs to current tenant
            if ($category->tenant_id !== Auth::user()->tenant_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Category not found'
                ], 404);
            }

            // Check if category has courses
            if ($category->courses()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete category with existing courses'
                ], 409);
            }

            // Check if category has subcategories
            if ($category->children()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete category with subcategories'
                ], 409);
            }

            $category->delete();

            return response()->json([
                'success' => true,
                'message' => 'Category deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting category: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error deleting category'
            ], 500);
        }
    }

    /**
     * Get category tree structure
     */
    public function tree(): JsonResponse
    {
        try {
            $categories = Category::with(['children' => function ($query) {
                $query->orderBy('name');
            }])
                ->where('tenant_id', Auth::user()->tenant_id)
                ->whereNull('parent_id')
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $categories,
                'message' => 'Category tree retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving category tree: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving category tree'
            ], 500);
        }
    }

    /**
     * Get categories suitable for dropdown selection
     */
    public function dropdown(): JsonResponse
    {
        try {
            $categories = Category::where('tenant_id', Auth::user()->tenant_id)
                ->orderBy('name')
                ->get(['id', 'name', 'parent_id']);

            return response()->json([
                'success' => true,
                'data' => $categories,
                'message' => 'Categories for dropdown retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving categories for dropdown: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving categories for dropdown'
            ], 500);
        }
    }

    /**
     * Check if moving a category would create circular reference
     */
    private function wouldCreateCircularReference(Category $category, int $newParentId): bool
    {
        $currentCategory = Category::find($newParentId);

        while ($currentCategory) {
            if ($currentCategory->id === $category->id) {
                return true;
            }
            $currentCategory = $currentCategory->parent;
        }

        return false;
    }
}
