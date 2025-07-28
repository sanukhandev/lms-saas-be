<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Course\CourseService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\Course;
use Illuminate\Support\Facades\DB;

class CourseBuilderController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private readonly CourseService $courseService
    ) {}

    /**
     * Get the course structure for the builder interface
     */
    public function getCourseStructure(string $courseId): JsonResponse
    {
        try {
            $tenantId = $this->getTenantId();
            Log::info('Fetching course structure', [
                'course_id' => $courseId,
                'tenant_id' => $tenantId
            ]);
            
            // Find the course and eager load its tree structure
            $course = Course::where('id', $courseId)
                ->where('tenant_id', $tenantId)
                ->where(function($query) {
                    $query->where('content_type', 'course')
                        ->orWhereNull('content_type');
                })
                ->first();
            
            if (!$course) {
                return $this->errorResponse(
                    message: 'Course not found',
                    code: 404
                );
            }

            // Get modules (direct children with content_type=module)
            $modules = Course::where('parent_id', $courseId)
                ->where('content_type', 'module')
                ->orderBy('position')
                ->get();
            
            $moduleData = collect();
            
            if ($modules->isNotEmpty()) {
                $moduleData = $modules->map(function ($module) {
                    // Get chapters for this module
                    $chapters = Course::where('parent_id', $module->id)
                        ->where('content_type', 'chapter')
                        ->orderBy('position')
                        ->get();
                    
                    $chapterData = collect();
                    
                    if ($chapters->isNotEmpty()) {
                        $chapterData = $chapters->map(function ($chapter) {
                            return [
                                'id' => $chapter->id,
                                'title' => $chapter->title,
                                'description' => $chapter->description ?? '',
                                'position' => $chapter->position ?? 0,
                                'duration_minutes' => $chapter->duration_minutes ?? 0,
                                'video_url' => $chapter->video_url ?? '',
                                'is_completed' => false,
                                'content' => $chapter->content ?? '',
                                'learning_objectives' => $chapter->learning_objectives ?? [],
                                'created_at' => $chapter->created_at,
                                'updated_at' => $chapter->updated_at,
                            ];
                        })->sortBy('position')->values()->toArray();
                    }
                    
                    return [
                        'id' => $module->id,
                        'title' => $module->title,
                        'description' => $module->description ?? '',
                        'position' => $module->position ?? 0,
                        'duration_hours' => $module->duration_hours ?? 0,
                        'chapters_count' => $chapters->count(),
                        'chapters' => $chapterData,
                        'created_at' => $module->created_at,
                        'updated_at' => $module->updated_at,
                    ];
                })->sortBy('position')->values()->toArray();
            }

            // Count all chapters across all modules
            $totalChapters = 0;
            foreach ($modules as $module) {
                $chapterCount = Course::where('parent_id', $module->id)
                    ->where('content_type', 'chapter')
                    ->count();
                $totalChapters += $chapterCount;
            }

            $structure = [
                'course_id' => $course->id,
                'title' => $course->title ?? '',
                'description' => $course->description ?? '',
                'status' => $course->status ?? 'draft',
                'is_active' => $course->is_active ?? false,
                'modules' => $moduleData,
                'total_duration' => $course->duration_hours ?? 0,
                'total_chapters' => $totalChapters,
                'created_at' => $course->created_at,
                'updated_at' => $course->updated_at,
            ];

            return $this->successResponse(
                $structure,
                'Course structure retrieved successfully'
            );
        } catch (\Exception $e) {
            Log::error('Error retrieving course structure', [
                'error' => $e->getMessage(),
                'course_id' => $courseId,
                'tenant_id' => $this->getTenantId(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                message: 'Failed to retrieve course structure: ' . $e->getMessage(),
                code: 500
            );
        }
    }

    /**
     * Create a new module for a course
     */
    public function createModule(Request $request, string $courseId): JsonResponse
    {
        try {
            $tenantId = $this->getTenantId();
            $course = Course::where('id', $courseId)
                ->where('tenant_id', $tenantId)
                ->where(function($query) {
                    $query->where('content_type', 'course')
                        ->orWhereNull('content_type');
                })
                ->first();
            
            if (!$course) {
                return $this->errorResponse(
                    message: 'Course not found',
                    code: 404
                );
            }

            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'position' => 'nullable|integer|min:0',
                'duration_hours' => 'nullable|numeric|min:0',
            ]);

            // Find the highest position if not provided
            if (!isset($validated['position'])) {
                $highestPosition = Course::where('parent_id', $courseId)
                    ->where('content_type', 'module')
                    ->max('position') ?? -1;
                $validated['position'] = $highestPosition + 1;
            }

            $module = new Course([
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'position' => $validated['position'],
                'parent_id' => $courseId,
                'content_type' => 'module',
                'tenant_id' => $tenantId,
                'duration_hours' => $validated['duration_hours'] ?? null,
            ]);

            $module->save();

            return $this->successResponse(
                $module->toArray(),
                'Module created successfully',
                201
            );
        } catch (\Exception $e) {
            Log::error('Error creating module', [
                'error' => $e->getMessage(),
                'course_id' => $courseId,
                'tenant_id' => $this->getTenantId(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                message: 'Failed to create module: ' . $e->getMessage(),
                code: 500
            );
        }
    }

    /**
     * Update an existing module
     */
    public function updateModule(Request $request, string $moduleId): JsonResponse
    {
        try {
            $tenantId = $this->getTenantId();
            $module = Course::where('id', $moduleId)
                ->where('content_type', 'module')
                ->first();
            
            if (!$module) {
                return $this->errorResponse(
                    message: 'Module not found',
                    code: 404
                );
            }
            
            // Get parent course to check tenant access
            $parentCourse = Course::find($module->parent_id);
            
            // Check tenant access
            if (!$parentCourse || $parentCourse->tenant_id !== $tenantId) {
                return $this->errorResponse(
                    message: 'Unauthorized access to module',
                    code: 403
                );
            }

            $validated = $request->validate([
                'title' => 'nullable|string|max:255',
                'description' => 'nullable|string',
                'position' => 'nullable|integer|min:0',
                'duration_hours' => 'nullable|numeric|min:0',
            ]);

            $module->fill(array_filter($validated));
            $module->save();

            return $this->successResponse(
                $module->toArray(),
                'Module updated successfully'
            );
        } catch (\Exception $e) {
            Log::error('Error updating module', [
                'error' => $e->getMessage(),
                'module_id' => $moduleId,
                'tenant_id' => $this->getTenantId(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                message: 'Failed to update module: ' . $e->getMessage(),
                code: 500
            );
        }
    }

    /**
     * Delete a module and its chapters
     */
    public function deleteModule(string $moduleId): JsonResponse
    {
        try {
            $tenantId = $this->getTenantId();
            $module = Course::where('id', $moduleId)
                ->where('content_type', 'module')
                ->first();
                
            if (!$module) {
                return $this->errorResponse(
                    message: 'Module not found',
                    code: 404
                );
            }
            
            // Get parent course to check tenant access
            $parentCourse = Course::find($module->parent_id);
            
            // Check tenant access
            if (!$parentCourse || $parentCourse->tenant_id !== $tenantId) {
                return $this->errorResponse(
                    message: 'Unauthorized access to module',
                    code: 403
                );
            }

            // Start a transaction for safely deleting the module and its children
            DB::beginTransaction();
            
            try {
                // Delete all chapters in this module
                Course::where('parent_id', $moduleId)->where('content_type', 'chapter')->delete();
                
                // Delete the module
                $module->delete();
                
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

            return $this->successResponse(
                [],
                'Module deleted successfully'
            );
        } catch (\Exception $e) {
            Log::error('Error deleting module', [
                'error' => $e->getMessage(),
                'module_id' => $moduleId,
                'tenant_id' => $this->getTenantId(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                message: 'Failed to delete module: ' . $e->getMessage(),
                code: 500
            );
        }
    }

    /**
     * Create a new chapter for a module
     */
    public function createChapter(Request $request, string $courseId, string $moduleId): JsonResponse
    {
        try {
            $tenantId = $this->getTenantId();
            
            // First verify the course exists and belongs to tenant
            $course = Course::where('id', $courseId)
                ->where('tenant_id', $tenantId)
                ->where(function($query) {
                    $query->where('content_type', 'course')
                        ->orWhereNull('content_type');
                })
                ->first();
                
            if (!$course) {
                return $this->errorResponse(
                    message: 'Course not found',
                    code: 404
                );
            }
            
            // Now verify the module exists and belongs to the course
            $module = Course::where('id', $moduleId)
                ->where('parent_id', $courseId)
                ->where('content_type', 'module')
                ->first();
                
            if (!$module) {
                return $this->errorResponse(
                    message: 'Module not found or does not belong to the specified course',
                    code: 404
                );
            }

            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'position' => 'nullable|integer|min:0',
                'duration_minutes' => 'nullable|numeric',
                'video_url' => 'nullable|string|url',
                'content' => 'nullable|string',
                'learning_objectives' => 'nullable|array',
            ]);

            // Find the highest position if not provided
            if (!isset($validated['position'])) {
                $highestPosition = Course::where('parent_id', $moduleId)
                    ->where('content_type', 'chapter')
                    ->max('position') ?? -1;
                $validated['position'] = $highestPosition + 1;
            }

            $chapter = new Course([
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'position' => $validated['position'],
                'parent_id' => $moduleId,
                'content_type' => 'chapter',
                'tenant_id' => $tenantId,
                'duration_minutes' => $validated['duration_minutes'] ?? null,
                'video_url' => $validated['video_url'] ?? null,
                'content' => $validated['content'] ?? null,
                'learning_objectives' => $validated['learning_objectives'] ?? null,
            ]);

            $chapter->save();

            return $this->successResponse(
                $chapter->toArray(),
                'Chapter created successfully',
                201
            );
        } catch (\Exception $e) {
            Log::error('Error creating chapter', [
                'error' => $e->getMessage(),
                'course_id' => $courseId,
                'module_id' => $moduleId,
                'tenant_id' => $this->getTenantId(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                message: 'Failed to create chapter: ' . $e->getMessage(),
                code: 500
            );
        }
    }

    /**
     * Update an existing chapter
     */
    public function updateChapter(Request $request, string $chapterId): JsonResponse
    {
        try {
            $tenantId = $this->getTenantId();
            $chapter = Course::where('id', $chapterId)
                ->where('content_type', 'chapter')
                ->first();
                
            if (!$chapter) {
                return $this->errorResponse(
                    message: 'Chapter not found',
                    code: 404
                );
            }
            
            // Get module to check tenant access
            $module = Course::where('id', $chapter->parent_id)
                ->where('content_type', 'module')
                ->first();
                
            if (!$module) {
                return $this->errorResponse(
                    message: 'Parent module not found',
                    code: 404
                );
            }
            
            // Get course to check tenant access
            $course = Course::where('id', $module->parent_id)
                ->where(function($query) {
                    $query->where('content_type', 'course')
                        ->orWhereNull('content_type');
                })
                ->first();
                
            // Check tenant access
            if (!$course || $course->tenant_id !== $tenantId) {
                return $this->errorResponse(
                    message: 'Unauthorized access to chapter',
                    code: 403
                );
            }

            $validated = $request->validate([
                'title' => 'nullable|string|max:255',
                'description' => 'nullable|string',
                'position' => 'nullable|integer|min:0',
                'duration_minutes' => 'nullable|numeric',
                'video_url' => 'nullable|string|url',
                'content' => 'nullable|string',
                'learning_objectives' => 'nullable|array',
            ]);

            $chapter->fill(array_filter($validated));
            $chapter->save();

            return $this->successResponse(
                $chapter->toArray(),
                'Chapter updated successfully'
            );
        } catch (\Exception $e) {
            Log::error('Error updating chapter', [
                'error' => $e->getMessage(),
                'chapter_id' => $chapterId,
                'tenant_id' => $this->getTenantId(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                message: 'Failed to update chapter: ' . $e->getMessage(),
                code: 500
            );
        }
    }

    /**
     * Delete a chapter
     */
    public function deleteChapter(string $chapterId): JsonResponse
    {
        try {
            $tenantId = $this->getTenantId();
            $chapter = Course::where('id', $chapterId)
                ->where('content_type', 'chapter')
                ->first();
                
            if (!$chapter) {
                return $this->errorResponse(
                    message: 'Chapter not found',
                    code: 404
                );
            }
            
            // Get module to check tenant access
            $module = Course::where('id', $chapter->parent_id)
                ->where('content_type', 'module')
                ->first();
                
            if (!$module) {
                return $this->errorResponse(
                    message: 'Parent module not found',
                    code: 404
                );
            }
            
            // Get course to check tenant access
            $course = Course::where('id', $module->parent_id)
                ->where(function($query) {
                    $query->where('content_type', 'course')
                        ->orWhereNull('content_type');
                })
                ->first();
                
            // Check tenant access
            if (!$course || $course->tenant_id !== $tenantId) {
                return $this->errorResponse(
                    message: 'Unauthorized access to chapter',
                    code: 403
                );
            }

            $chapter->delete();

            return $this->successResponse(
                [],
                'Chapter deleted successfully'
            );
        } catch (\Exception $e) {
            Log::error('Error deleting chapter', [
                'error' => $e->getMessage(),
                'chapter_id' => $chapterId,
                'tenant_id' => $this->getTenantId(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                message: 'Failed to delete chapter: ' . $e->getMessage(),
                code: 500
            );
        }
    }

    /**
     * Reorder course content (modules and chapters)
     */
    public function reorderContent(Request $request, string $courseId): JsonResponse
    {
        try {
            $tenantId = $this->getTenantId();
            $course = Course::where('id', $courseId)
                ->where('tenant_id', $tenantId)
                ->where(function($query) {
                    $query->where('content_type', 'course')
                        ->orWhereNull('content_type');
                })
                ->firstOrFail();

            $validated = $request->validate([
                'items' => 'required|array',
                'items.*.id' => 'required|string',
                'items.*.position' => 'required|integer|min:0',
                'items.*.parent_id' => 'required|string',
            ]);

            DB::beginTransaction();

            foreach ($validated['items'] as $item) {
                // Update position and parent_id for each item
                $contentItem = Course::find($item['id']);
                if ($contentItem) {
                    $contentItem->position = $item['position'];
                    $contentItem->parent_id = $item['parent_id'];
                    $contentItem->save();
                }
            }

            DB::commit();

            return $this->successResponse(
                [],
                'Content reordered successfully'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error reordering content', [
                'error' => $e->getMessage(),
                'course_id' => $courseId,
                'tenant_id' => $this->getTenantId(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                message: 'Failed to reorder content: ' . $e->getMessage(),
                code: 500
            );
        }
    }

    /**
     * Get course pricing information
     */
    public function getCoursePricing(string $courseId): JsonResponse
    {
        try {
            $tenantId = $this->getTenantId();
            $course = Course::where('id', $courseId)
                ->where('tenant_id', $tenantId)
                ->where(function($query) {
                    $query->where('content_type', 'course')
                        ->orWhereNull('content_type');
                })
                ->firstOrFail();

            $pricing = [
                'course_id' => $course->id,
                'access_model' => $course->pricing_model ?? 'one_time',
                'base_price' => $course->price ?? 0,
                'base_currency' => $course->currency ?? 'USD',
                'discount_percentage' => $course->discount_percentage ?? 0,
                'discounted_price' => $course->discounted_price ?? null,
                'subscription_price' => $course->subscription_price ?? null,
                'trial_period_days' => $course->trial_period_days ?? null,
                'is_active' => $course->is_active,
                'enabled_access_models' => ['one_time', 'monthly_subscription', 'full_curriculum'],
                'created_at' => $course->created_at,
                'updated_at' => $course->updated_at,
            ];

            return $this->successResponse(
                $pricing,
                'Course pricing retrieved successfully'
            );
        } catch (\Exception $e) {
            Log::error('Error retrieving course pricing', [
                'error' => $e->getMessage(),
                'course_id' => $courseId,
                'tenant_id' => $this->getTenantId(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                message: 'Failed to retrieve course pricing: ' . $e->getMessage(),
                code: 500
            );
        }
    }

    /**
     * Update course pricing
     */
    public function updateCoursePricing(Request $request, string $courseId): JsonResponse
    {
        try {
            $tenantId = $this->getTenantId();
            $course = Course::where('id', $courseId)
                ->where('tenant_id', $tenantId)
                ->where(function($query) {
                    $query->where('content_type', 'course')
                        ->orWhereNull('content_type');
                })
                ->first();
            
            if (!$course) {
                return $this->errorResponse(
                    message: 'Course not found',
                    code: 404
                );
            }

            $validated = $request->validate([
                'access_model' => 'nullable|string|in:one_time,monthly_subscription,full_curriculum',
                'base_price' => 'nullable|numeric|min:0',
                'discount_percentage' => 'nullable|numeric|min:0|max:100',
                'subscription_price' => 'nullable|numeric|min:0',
                'trial_period_days' => 'nullable|integer|min:0',
                'is_active' => 'nullable|boolean',
            ]);

            $updateData = [];
            
            if (isset($validated['access_model'])) {
                $updateData['pricing_model'] = $validated['access_model'];
            }
            
            if (isset($validated['base_price'])) {
                $updateData['price'] = $validated['base_price'];
            }
            
            if (isset($validated['discount_percentage'])) {
                $updateData['discount_percentage'] = $validated['discount_percentage'];
                
                // Calculate discounted price
                if (isset($updateData['price']) || $course->price) {
                    $basePrice = $updateData['price'] ?? $course->price;
                    $updateData['discounted_price'] = $basePrice * (1 - $validated['discount_percentage'] / 100);
                }
            }
            
            if (isset($validated['subscription_price'])) {
                $updateData['subscription_price'] = $validated['subscription_price'];
            }
            
            if (isset($validated['trial_period_days'])) {
                $updateData['trial_period_days'] = $validated['trial_period_days'];
            }
            
            if (isset($validated['is_active'])) {
                $updateData['is_active'] = $validated['is_active'];
            }

            DB::beginTransaction();
            try {
                $course->update($updateData);
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

            $course->refresh(); // Reload the model after update

            return $this->successResponse(
                [
                    'course_id' => $course->id,
                    'access_model' => $course->pricing_model ?? 'one_time',
                    'base_price' => $course->price ?? 0,
                    'base_currency' => $course->currency ?? 'USD',
                    'discount_percentage' => $course->discount_percentage ?? 0,
                    'discounted_price' => $course->discounted_price ?? null,
                    'subscription_price' => $course->subscription_price ?? null,
                    'trial_period_days' => $course->trial_period_days ?? null,
                    'is_active' => $course->is_active,
                    'enabled_access_models' => ['one_time', 'monthly_subscription', 'full_curriculum'],
                    'created_at' => $course->created_at,
                    'updated_at' => $course->updated_at,
                ],
                'Course pricing updated successfully'
            );
        } catch (\Exception $e) {
            Log::error('Error updating course pricing', [
                'error' => $e->getMessage(),
                'course_id' => $courseId,
                'tenant_id' => $this->getTenantId(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                message: 'Failed to update course pricing: ' . $e->getMessage(),
                code: 500
            );
        }
    }

    /**
     * Get supported access models
     */
    public function getSupportedAccessModels(): JsonResponse
    {
        try {
            $models = ['one_time', 'monthly_subscription', 'full_curriculum'];
            
            return $this->successResponse(
                $models,
                'Supported access models retrieved successfully'
            );
        } catch (\Exception $e) {
            Log::error('Error retrieving access models', [
                'error' => $e->getMessage(),
                'tenant_id' => $this->getTenantId(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                message: 'Failed to retrieve access models',
                code: 500
            );
        }
    }

    /**
     * Get local pricing options for a course
     */
    public function getLocalPricing(string $courseId): JsonResponse
    {
        try {
            $tenantId = $this->getTenantId();
            $course = Course::where('id', $courseId)->where('tenant_id', $tenantId)->firstOrFail();
            
            // This is a placeholder - in a real implementation, we would fetch 
            // localized pricing from a service or database
            $localPricing = [
                'course_id' => $courseId,
                'local_prices' => [
                    [
                        'country' => 'US',
                        'currency' => 'USD',
                        'price' => $course->price ?? 99.99,
                        'symbol' => '$',
                    ],
                    [
                        'country' => 'GB',
                        'currency' => 'GBP',
                        'price' => ($course->price ?? 99.99) * 0.8,
                        'symbol' => '£',
                    ],
                    [
                        'country' => 'EU',
                        'currency' => 'EUR',
                        'price' => ($course->price ?? 99.99) * 0.9,
                        'symbol' => '€',
                    ],
                ],
            ];
            
            return $this->successResponse(
                $localPricing,
                'Local pricing retrieved successfully'
            );
        } catch (\Exception $e) {
            Log::error('Error retrieving local pricing', [
                'error' => $e->getMessage(),
                'course_id' => $courseId,
                'tenant_id' => $this->getTenantId(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                message: 'Failed to retrieve local pricing',
                code: 500
            );
        }
    }

    /**
     * Get bulk pricing options
     */
    public function getBulkPricing(): JsonResponse
    {
        try {
            // This is a placeholder - in a real implementation, we would fetch 
            // bulk pricing options from a service or database
            $bulkPricing = [
                'bulk_discounts' => [
                    [
                        'min_quantity' => 5,
                        'max_quantity' => 9,
                        'discount_percentage' => 10,
                    ],
                    [
                        'min_quantity' => 10,
                        'max_quantity' => 19,
                        'discount_percentage' => 15,
                    ],
                    [
                        'min_quantity' => 20,
                        'max_quantity' => null,
                        'discount_percentage' => 20,
                    ],
                ],
            ];
            
            return $this->successResponse(
                $bulkPricing,
                'Bulk pricing retrieved successfully'
            );
        } catch (\Exception $e) {
            Log::error('Error retrieving bulk pricing', [
                'error' => $e->getMessage(),
                'tenant_id' => $this->getTenantId(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                message: 'Failed to retrieve bulk pricing',
                code: 500
            );
        }
    }

    /**
     * Validate pricing configuration
     */
    public function validatePricing(Request $request, string $courseId): JsonResponse
    {
        try {
            $tenantId = $this->getTenantId();
            $course = Course::where('id', $courseId)->where('tenant_id', $tenantId)->firstOrFail();
            
            // Placeholder for validation logic
            $issues = [];
            
            if (empty($course->price) && $course->pricing_model === 'one_time') {
                $issues[] = 'One-time payment model requires a base price.';
            }
            
            if (empty($course->subscription_price) && $course->pricing_model === 'monthly_subscription') {
                $issues[] = 'Subscription model requires a subscription price.';
            }
            
            return $this->successResponse(
                [
                    'valid' => count($issues) === 0,
                    'course_id' => $courseId,
                    'issues' => $issues,
                ],
                'Pricing validation completed'
            );
        } catch (\Exception $e) {
            Log::error('Error validating pricing', [
                'error' => $e->getMessage(),
                'course_id' => $courseId,
                'tenant_id' => $this->getTenantId(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                message: 'Failed to validate pricing',
                code: 500
            );
        }
    }

    /**
     * Publish a course
     */
    public function publishCourse(string $courseId): JsonResponse
    {
        try {
            $tenantId = $this->getTenantId();
            $course = Course::where('id', $courseId)
                ->where('tenant_id', $tenantId)
                ->where(function($query) {
                    $query->where('content_type', 'course')
                        ->orWhereNull('content_type');
                })
                ->first();

            if (!$course) {
                return $this->errorResponse(
                    message: 'Course not found',
                    code: 404
                );
            }

            // Check if the course has at least one module with one chapter
            $hasModules = Course::where('parent_id', $courseId)
                ->where('content_type', 'module')
                ->exists();
                
            $hasChapters = false;
            if ($hasModules) {
                $modules = Course::where('parent_id', $courseId)
                    ->where('content_type', 'module')
                    ->get();
                    
                foreach ($modules as $module) {
                    if (Course::where('parent_id', $module->id)
                        ->where('content_type', 'chapter')
                        ->exists()) {
                        $hasChapters = true;
                        break;
                    }
                }
            }
            
            if (!$hasModules || !$hasChapters) {
                return $this->errorResponse(
                    message: 'Course must have at least one module with one chapter to be published',
                    code: 422
                );
            }

            DB::beginTransaction();
            try {
                $course->update([
                    'status' => 'published',
                    'published_at' => now(),
                ]);
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

            $course->refresh(); // Reload the model after update

            return $this->successResponse(
                $course->toArray(),
                'Course published successfully'
            );
        } catch (\Exception $e) {
            Log::error('Error publishing course', [
                'error' => $e->getMessage(),
                'course_id' => $courseId,
                'tenant_id' => $this->getTenantId(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                message: 'Failed to publish course: ' . $e->getMessage(),
                code: 500
            );
        }
    }

    /**
     * Unpublish a course
     */
    public function unpublishCourse(string $courseId): JsonResponse
    {
        try {
            $tenantId = $this->getTenantId();
            $course = Course::where('id', $courseId)
                ->where('tenant_id', $tenantId)
                ->where(function($query) {
                    $query->where('content_type', 'course')
                        ->orWhereNull('content_type');
                })
                ->first();

            if (!$course) {
                return $this->errorResponse(
                    message: 'Course not found',
                    code: 404
                );
            }

            DB::beginTransaction();
            try {
                $course->update([
                    'status' => 'draft',
                    'unpublished_at' => now(),
                ]);
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

            $course->refresh(); // Reload the model after update

            return $this->successResponse(
                $course->toArray(),
                'Course unpublished successfully'
            );
        } catch (\Exception $e) {
            Log::error('Error unpublishing course', [
                'error' => $e->getMessage(),
                'course_id' => $courseId,
                'tenant_id' => $this->getTenantId(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                message: 'Failed to unpublish course: ' . $e->getMessage(),
                code: 500
            );
        }
    }

    /**
     * Get tenant ID from authenticated user
     */
    private function getTenantId(): string
    {
        if (!Auth::check()) {
            Log::error('User not authenticated when trying to get tenant ID');
            abort(401, 'Unauthenticated');
        }
        
        $user = Auth::user();
        if (!$user || !$user->tenant_id) {
            Log::error('User has no tenant ID', [
                'user_id' => $user ? $user->id : 'unknown'
            ]);
            abort(403, 'No tenant ID found for user');
        }
        
        return $user->tenant_id;
    }
}
