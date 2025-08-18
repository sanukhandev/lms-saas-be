<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class CourseContent extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'course_id',
        'parent_id',
        'type',
        'title',
        'description',
        'content',
        'content_data',
        'video_url',
        'file_path',
        'file_type',
        'file_size',
        'learning_objectives',
        'status',
        'is_required',
        'is_free',
        'published_at',
        'position',
        'sort_order',
        'duration_mins',
        'estimated_duration',
        'tenant_id'
    ];

    protected $casts = [
        'content_data' => 'array',
        'learning_objectives' => 'array',
        'is_required' => 'boolean',
        'is_free' => 'boolean',
        'published_at' => 'datetime',
        'file_size' => 'integer',
        'position' => 'integer',
        'sort_order' => 'integer',
        'duration_mins' => 'integer',
        'estimated_duration' => 'integer',
    ];

    // Relationships
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(CourseContent::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(CourseContent::class, 'parent_id')->orderBy('sort_order');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(ClassSession::class, 'content_id');
    }

    public function materials(): HasMany
    {
        return $this->hasMany(CourseMaterial::class, 'content_id');
    }

    // Scopes
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }

    public function scopeFree($query)
    {
        return $query->where('is_free', true);
    }

    // Accessors
    public function getFileUrlAttribute(): ?string
    {
        if (!$this->file_path) {
            return null;
        }

        return asset('storage/' . $this->file_path);
    }

    public function getFormattedFileSizeAttribute(): ?string
    {
        if (!$this->file_size) {
            return null;
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = $this->file_size;

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function getFormattedDurationAttribute(): ?string
    {
        $duration = $this->estimated_duration ?? $this->duration_mins;

        if (!$duration) {
            return null;
        }

        if ($duration < 60) {
            return $duration . ' min';
        }

        $hours = floor($duration / 60);
        $minutes = $duration % 60;

        if ($minutes === 0) {
            return $hours . ' hr';
        }

        return $hours . ' hr ' . $minutes . ' min';
    }

    // Methods
    public function isVideo(): bool
    {
        return $this->type === 'video' || !empty($this->video_url);
    }

    public function hasFile(): bool
    {
        return !empty($this->file_path);
    }

    public function isPublished(): bool
    {
        return $this->status === 'published' && $this->published_at !== null;
    }

    public function canBeAccessed(): bool
    {
        return $this->isPublished() || $this->is_free;
    }

    public function getContentTypeIcon(): string
    {
        return match ($this->type) {
            'video' => 'play-circle',
            'document' => 'file-text',
            'quiz' => 'help-circle',
            'assignment' => 'clipboard',
            'text' => 'type',
            'live_session' => 'video',
            'lesson' => 'book-open',
            'module' => 'folder',
            'chapter' => 'bookmark',
            default => 'file'
        };
    }

    public function getTotalChildrenDuration(): int
    {
        return $this->children()
            ->sum('estimated_duration') ?? 0;
    }

    public function getHierarchyLevel(): int
    {
        $level = 0;
        $current = $this;

        while ($current->parent) {
            $level++;
            $current = $current->parent;
        }

        return $level;
    }
}
