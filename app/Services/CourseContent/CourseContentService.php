<?php

namespace App\Services\CourseContent;

use App\DTOs\CourseContent\CourseContentDTO;
use App\DTOs\CourseContent\CourseContentStatsDTO;
use App\Models\Course;
use App\Models\CourseContent;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CourseContentService
{
    /**
     * Get course content list with caching
     */
    public function getContentList(int $courseId, array $filters = []): array
    {
        $tenantId = Auth::user()->tenant_id;
        $cacheKey = "course_content_list_{$courseId}_{$tenantId}_" . md5(serialize($filters));
        
        return Cache::remember($cacheKey, 600, function () use ($courseId, $filters, $tenantId) {
            try {
                // Check if course belongs to current tenant
                $course = Course::where('id', $courseId)
                    ->where('tenant_id', $tenantId)
                    ->first();
                    
                if (!$course) {
                    return ['success' => false, 'message' => 'Course not found'];
                }

                $query = CourseContent::where('course_id', $courseId)
                    ->orderBy('order_index', 'asc')
                    ->orderBy('created_at', 'desc');

                // Apply filters
                if (!empty($filters['status'])) {
                    $query->where('status', $filters['status']);
                }

                if (!empty($filters['type'])) {
                    $query->where('type', $filters['type']);
                }

                if (!empty($filters['search'])) {
                    $query->where(function ($q) use ($filters) {
                        $q->where('title', 'like', '%' . $filters['search'] . '%')
                          ->orWhere('description', 'like', '%' . $filters['search'] . '%');
                    });
                }

                $perPage = $filters['per_page'] ?? 15;
                $contents = $query->paginate($perPage);

                // Transform to DTOs
                $contentDTOs = $contents->getCollection()->map(function ($content) {
                    return new CourseContentDTO(
                        $content->id,
                        $content->course_id,
                        $content->title,
                        $content->description,
                        $content->type,
                        $content->content_url,
                        $content->content_data,
                        $content->order_index,
                        $content->status,
                        $content->duration_minutes,
                        $content->is_required,
                        $content->created_at,
                        $content->updated_at
                    );
                });

                return [
                    'success' => true,
                    'data' => [
                        'contents' => $contentDTOs->map(fn($dto) => $dto->toArray()),
                        'pagination' => [
                            'current_page' => $contents->currentPage(),
                            'last_page' => $contents->lastPage(),
                            'per_page' => $contents->perPage(),
                            'total' => $contents->total(),
                            'from' => $contents->firstItem(),
                            'to' => $contents->lastItem()
                        ]
                    ]
                ];

            } catch (\Exception $e) {
                Log::error('Error fetching course content list', [
                    'course_id' => $courseId,
                    'tenant_id' => $tenantId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                
                return ['success' => false, 'message' => 'Failed to fetch course content'];
            }
        });
    }

    /**
     * Get single course content by ID
     */
    public function getContentById(int $courseId, int $contentId): array
    {
        $tenantId = Auth::user()->tenant_id;
        $cacheKey = "course_content_{$contentId}_{$courseId}_{$tenantId}";
        
        return Cache::remember($cacheKey, 900, function () use ($courseId, $contentId, $tenantId) {
            try {
                // Check if course belongs to current tenant
                $course = Course::where('id', $courseId)
                    ->where('tenant_id', $tenantId)
                    ->first();
                    
                if (!$course) {
                    return ['success' => false, 'message' => 'Course not found'];
                }

                $content = CourseContent::where('id', $contentId)
                    ->where('course_id', $courseId)
                    ->first();

                if (!$content) {
                    return ['success' => false, 'message' => 'Content not found'];
                }

                $contentDTO = new CourseContentDTO(
                    $content->id,
                    $content->course_id,
                    $content->title,
                    $content->description,
                    $content->type,
                    $content->content_url,
                    $content->content_data,
                    $content->order_index,
                    $content->status,
                    $content->duration_minutes,
                    $content->is_required,
                    $content->created_at,
                    $content->updated_at
                );

                return [
                    'success' => true,
                    'data' => $contentDTO->toArray()
                ];

            } catch (\Exception $e) {
                Log::error('Error fetching course content', [
                    'content_id' => $contentId,
                    'course_id' => $courseId,
                    'tenant_id' => $tenantId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                
                return ['success' => false, 'message' => 'Failed to fetch course content'];
            }
        });
    }

    /**
     * Create new course content
     */
    public function createContent(int $courseId, array $data): array
    {
        $tenantId = Auth::user()->tenant_id;
        
        try {
            // Check if course belongs to current tenant
            $course = Course::where('id', $courseId)
                ->where('tenant_id', $tenantId)
                ->first();
                
            if (!$course) {
                return ['success' => false, 'message' => 'Course not found'];
            }

            DB::beginTransaction();

            // Handle file upload if present
            if (!empty($data['content_file'])) {
                $file = $data['content_file'];
                $path = $file->store('course-content/' . $courseId, 'public');
                $data['content_url'] = Storage::url($path);
                unset($data['content_file']);
            }

            // Set order index if not provided
            if (!isset($data['order_index'])) {
                $maxOrder = CourseContent::where('course_id', $courseId)->max('order_index') ?? 0;
                $data['order_index'] = $maxOrder + 1;
            }

            $data['course_id'] = $courseId;
            $content = CourseContent::create($data);

            DB::commit();

            // Clear related caches
            $this->clearContentCaches($courseId, $tenantId);

            $contentDTO = new CourseContentDTO(
                $content->id,
                $content->course_id,
                $content->title,
                $content->description,
                $content->type,
                $content->content_url,
                $content->content_data,
                $content->order_index,
                $content->status,
                $content->duration_minutes,
                $content->is_required,
                $content->created_at,
                $content->updated_at
            );

            return [
                'success' => true,
                'message' => 'Content created successfully',
                'data' => $contentDTO->toArray()
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error creating course content', [
                'course_id' => $courseId,
                'tenant_id' => $tenantId,
                'data' => $data,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return ['success' => false, 'message' => 'Failed to create content'];
        }
    }

    /**
     * Update course content
     */
    public function updateContent(int $courseId, int $contentId, array $data): array
    {
        $tenantId = Auth::user()->tenant_id;
        
        try {
            // Check if course belongs to current tenant
            $course = Course::where('id', $courseId)
                ->where('tenant_id', $tenantId)
                ->first();
                
            if (!$course) {
                return ['success' => false, 'message' => 'Course not found'];
            }

            $content = CourseContent::where('id', $contentId)
                ->where('course_id', $courseId)
                ->first();

            if (!$content) {
                return ['success' => false, 'message' => 'Content not found'];
            }

            DB::beginTransaction();

            // Handle file upload if present
            if (!empty($data['content_file'])) {
                // Delete old file if exists
                if ($content->content_url) {
                    $oldPath = str_replace('/storage/', '', $content->content_url);
                    Storage::disk('public')->delete($oldPath);
                }

                $file = $data['content_file'];
                $path = $file->store('course-content/' . $courseId, 'public');
                $data['content_url'] = Storage::url($path);
                unset($data['content_file']);
            }

            $content->update($data);

            DB::commit();

            // Clear related caches
            $this->clearContentCaches($courseId, $tenantId, $contentId);

            $contentDTO = new CourseContentDTO(
                $content->id,
                $content->course_id,
                $content->title,
                $content->description,
                $content->type,
                $content->content_url,
                $content->content_data,
                $content->order_index,
                $content->status,
                $content->duration_minutes,
                $content->is_required,
                $content->created_at,
                $content->updated_at
            );

            return [
                'success' => true,
                'message' => 'Content updated successfully',
                'data' => $contentDTO->toArray()
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error updating course content', [
                'content_id' => $contentId,
                'course_id' => $courseId,
                'tenant_id' => $tenantId,
                'data' => $data,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return ['success' => false, 'message' => 'Failed to update content'];
        }
    }

    /**
     * Delete course content
     */
    public function deleteContent(int $courseId, int $contentId): array
    {
        $tenantId = Auth::user()->tenant_id;
        
        try {
            // Check if course belongs to current tenant
            $course = Course::where('id', $courseId)
                ->where('tenant_id', $tenantId)
                ->first();
                
            if (!$course) {
                return ['success' => false, 'message' => 'Course not found'];
            }

            $content = CourseContent::where('id', $contentId)
                ->where('course_id', $courseId)
                ->first();

            if (!$content) {
                return ['success' => false, 'message' => 'Content not found'];
            }

            DB::beginTransaction();

            // Delete associated file if exists
            if ($content->content_url) {
                $path = str_replace('/storage/', '', $content->content_url);
                Storage::disk('public')->delete($path);
            }

            $content->delete();

            DB::commit();

            // Clear related caches
            $this->clearContentCaches($courseId, $tenantId, $contentId);

            return [
                'success' => true,
                'message' => 'Content deleted successfully'
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error deleting course content', [
                'content_id' => $contentId,
                'course_id' => $courseId,
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return ['success' => false, 'message' => 'Failed to delete content'];
        }
    }

    /**
     * Reorder course content
     */
    public function reorderContent(int $courseId, array $orderData): array
    {
        $tenantId = Auth::user()->tenant_id;
        
        try {
            // Check if course belongs to current tenant
            $course = Course::where('id', $courseId)
                ->where('tenant_id', $tenantId)
                ->first();
                
            if (!$course) {
                return ['success' => false, 'message' => 'Course not found'];
            }

            DB::beginTransaction();

            foreach ($orderData as $item) {
                CourseContent::where('id', $item['id'])
                    ->where('course_id', $courseId)
                    ->update(['order_index' => $item['order_index']]);
            }

            DB::commit();

            // Clear related caches
            $this->clearContentCaches($courseId, $tenantId);

            return [
                'success' => true,
                'message' => 'Content reordered successfully'
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error reordering course content', [
                'course_id' => $courseId,
                'tenant_id' => $tenantId,
                'order_data' => $orderData,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return ['success' => false, 'message' => 'Failed to reorder content'];
        }
    }

    /**
     * Get course content statistics
     */
    public function getContentStats(int $courseId): array
    {
        $tenantId = Auth::user()->tenant_id;
        $cacheKey = "course_content_stats_{$courseId}_{$tenantId}";
        
        return Cache::remember($cacheKey, 900, function () use ($courseId, $tenantId) {
            try {
                // Check if course belongs to current tenant
                $course = Course::where('id', $courseId)
                    ->where('tenant_id', $tenantId)
                    ->first();
                    
                if (!$course) {
                    return ['success' => false, 'message' => 'Course not found'];
                }

                $totalContent = CourseContent::where('course_id', $courseId)->count();
                $publishedContent = CourseContent::where('course_id', $courseId)
                    ->where('status', 'published')
                    ->count();
                $draftContent = CourseContent::where('course_id', $courseId)
                    ->where('status', 'draft')
                    ->count();
                $totalDuration = CourseContent::where('course_id', $courseId)
                    ->where('status', 'published')
                    ->sum('duration_minutes') ?? 0;

                // Content by type
                $contentByType = CourseContent::where('course_id', $courseId)
                    ->selectRaw('type, COUNT(*) as count')
                    ->groupBy('type')
                    ->pluck('count', 'type')
                    ->toArray();

                $statsDTO = new CourseContentStatsDTO(
                    $totalContent,
                    $publishedContent,
                    $draftContent,
                    $totalDuration,
                    $contentByType
                );

                return [
                    'success' => true,
                    'data' => $statsDTO->toArray()
                ];

            } catch (\Exception $e) {
                Log::error('Error fetching course content stats', [
                    'course_id' => $courseId,
                    'tenant_id' => $tenantId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                
                return ['success' => false, 'message' => 'Failed to fetch content statistics'];
            }
        });
    }

    /**
     * Clear content related caches
     */
    private function clearContentCaches(int $courseId, int $tenantId, ?int $contentId = null): void
    {
        try {
            // Clear specific content cache if provided
            if ($contentId) {
                Cache::forget("course_content_{$contentId}_{$courseId}_{$tenantId}");
            }

            // Clear content stats cache
            Cache::forget("course_content_stats_{$courseId}_{$tenantId}");

            // Clear list caches (pattern-based clearing)
            $keys = Cache::getRedis()->keys("*course_content_list_{$courseId}_{$tenantId}*");
            foreach ($keys as $key) {
                Cache::forget(str_replace(config('cache.prefix') . ':', '', $key));
            }

        } catch (\Exception $e) {
            Log::error('Error clearing content caches', [
                'course_id' => $courseId,
                'content_id' => $contentId,
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
