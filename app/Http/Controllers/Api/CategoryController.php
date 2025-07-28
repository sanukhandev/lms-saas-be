<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Category\{CategoryIndexRequest, CreateCategoryRequest, UpdateCategoryRequest};
use App\Services\Category\CategoryService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\Category;

class CategoryController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private readonly CategoryService $categoryService
    ) {}

    /**
     * Display a listing of categories
     */
    public function index(CategoryIndexRequest $request): JsonResponse
    {
        try {
            $tenantId = $this->getTenantId();
            $filters = $request->validated();
            $page = $request->get('page', 1);
            $perPage = $request->get('per_page', 15);

            $categories = $this->categoryService->getCategoriesList($tenantId, $filters, $page, $perPage);

            return $this->successPaginated($categories, 'Categories retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Error retrieving categories list', [
                'error' => $e->getMessage(),
                'tenant_id' => $this->getTenantId(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                message: 'Failed to retrieve categories',
                code: 500
            );
        }
    }

    /**
     * Store a newly created category
     */
    public function store(CreateCategoryRequest $request): JsonResponse
    {
        try {
            $tenantId = $this->getTenantId();
            $data = $request->validated();

            $category = $this->categoryService->createCategory($data, $tenantId);

            return $this->successResponse(
                $category->toArray(),
                'Category created successfully',
                201
            );
        } catch (\Exception $e) {
            Log::error('Error creating category', [
                'error' => $e->getMessage(),
                'tenant_id' => $this->getTenantId(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                message: 'Failed to create category',
                code: 500
            );
        }
    }

    /**
     * Display the specified category
     */
    public function show(string $categoryId): JsonResponse
    {
        try {
            $category = Category::findOrFail($categoryId);
            $this->authorize('view', $category);
            $tenantId = $this->getTenantId();
            $categoryDto = $this->categoryService->getCategoryById($categoryId, $tenantId);

            if (!$categoryDto) {
                return $this->errorResponse(
                    message: 'Category not found',
                    code: 404
                );
            }

            return $this->successResponse(
                $categoryDto->toArray(),
                'Category retrieved successfully'
            );
        } catch (\Exception $e) {
            Log::error('Error retrieving category', [
                'error' => $e->getMessage(),
                'category_id' => $categoryId,
                'tenant_id' => $this->getTenantId(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                message: 'Failed to retrieve category',
                code: 500
            );
        }
    }

    /**
     * Update the specified category
     */
    public function update(UpdateCategoryRequest $request, string $categoryId): JsonResponse
    {
        try {
            $category = Category::findOrFail($categoryId);
            $this->authorize('update', $category);
            $tenantId = $this->getTenantId();
            $data = $request->validated();

            $categoryDto = $this->categoryService->updateCategory($categoryId, $data, $tenantId);

            if (!$categoryDto) {
                return $this->errorResponse(
                    message: 'Category not found',
                    code: 404
                );
            }

            return $this->successResponse(
                $categoryDto->toArray(),
                'Category updated successfully'
            );
        } catch (\Exception $e) {
            Log::error('Error updating category', [
                'error' => $e->getMessage(),
                'category_id' => $categoryId,
                'tenant_id' => $this->getTenantId(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                message: 'Failed to update category',
                code: 500
            );
        }
    }

    /**
     * Remove the specified category
     */
    public function destroy(string $categoryId): JsonResponse
    {
        try {
            $category = Category::findOrFail($categoryId);
            $this->authorize('delete', $category);
            $tenantId = $this->getTenantId();

            $success = $this->categoryService->deleteCategory($categoryId, $tenantId);

            if (!$success) {
                return $this->errorResponse(
                    message: 'Category not found',
                    code: 404
                );
            }

            return $this->successResponse(
                [],
                'Category deleted successfully'
            );
        } catch (\Exception $e) {
            Log::error('Error deleting category', [
                'error' => $e->getMessage(),
                'category_id' => $categoryId,
                'tenant_id' => $this->getTenantId(),
                'trace' => $e->getTraceAsString()
            ]);

            // Check for specific error messages
            if (str_contains($e->getMessage(), 'contains courses')) {
                return $this->errorResponse(
                    message: 'Cannot delete category that contains courses',
                    code: 409
                );
            }

            if (str_contains($e->getMessage(), 'has subcategories')) {
                return $this->errorResponse(
                    message: 'Cannot delete category that has subcategories',
                    code: 409
                );
            }

            return $this->errorResponse(
                message: 'Failed to delete category',
                code: 500
            );
        }
    }

    /**
     * Get category tree structure
     */
    public function tree(): JsonResponse
    {
        try {
            $tenantId = $this->getTenantId();
            $tree = $this->categoryService->getCategoryTree($tenantId);

            return $this->successResponse(
                $tree,
                'Category tree retrieved successfully'
            );
        } catch (\Exception $e) {
            Log::error('Error retrieving category tree', [
                'error' => $e->getMessage(),
                'tenant_id' => $this->getTenantId(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                message: 'Failed to retrieve category tree',
                code: 500
            );
        }
    }

    /**
     * Get categories for dropdown/select options
     */
    public function dropdown(): JsonResponse
    {
        try {
            $tenantId = $this->getTenantId();
            
            // Get simplified category list for dropdown
            $filters = ['is_active' => true];
            $categories = $this->categoryService->getCategoriesList($tenantId, $filters, 1, 1000);

            // Transform to simple dropdown format
            $dropdownOptions = $categories->getCollection()->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'parent_id' => $category->parentId,
                ];
            });

            return $this->successResponse(
                $dropdownOptions->toArray(),
                'Category dropdown options retrieved successfully'
            );
        } catch (\Exception $e) {
            Log::error('Error retrieving category dropdown', [
                'error' => $e->getMessage(),
                'tenant_id' => $this->getTenantId(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                message: 'Failed to retrieve category dropdown options',
                code: 500
            );
        }
    }

    /**
     * Get statistics for categories
     */
    public function statistics(): JsonResponse
    {
        try {
            $tenantId = $this->getTenantId();
            $stats = $this->categoryService->getCategoryStats($tenantId);

            return $this->successResponse(
                $stats->toArray(),
                'Category statistics retrieved successfully'
            );
        } catch (\Exception $e) {
            Log::error('Error retrieving category statistics', [
                'error' => $e->getMessage(),
                'tenant_id' => $this->getTenantId(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                message: 'Failed to retrieve category statistics',
                code: 500
            );
        }
    }

    /**
     * Get tenant ID from authenticated user
     */
    private function getTenantId(): string
    {
        return Auth::user()->tenant_id;
    }
}
