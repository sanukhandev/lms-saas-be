<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    use BelongsToTenant, HasFactory;
    protected $fillable = [
        'tenant_id',
        'category_id',
        'title',
        'description',
        'short_description',
        'schedule_level',
        'status',
        'is_active',
        'access_model',
        'price',
        'discount_percentage',
        'discounted_price',
        'subscription_price',
        'trial_period_days',
        'is_pricing_active',
        'slug',
        'currency',
        'level',
        'duration_hours',
        'instructor_id',
        'thumbnail_url',
        'preview_video_url',
        'requirements',
        'what_you_will_learn',
        'meta_description',
        'tags',
        'average_rating',
        'pricing_model',
        'published_at',
        'unpublished_at',
        // New tree structure fields
        'parent_id',
        'content_type',
        'position',
        'content',
        'learning_objectives',
        'video_url',
        'duration_minutes'
    ];
    
    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'learning_objectives' => 'json',
        'position' => 'integer',
        'duration_minutes' => 'float',
        'duration_hours' => 'float',
    ];

    public function tenant():BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function category():BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function contents():HasMany
    {
        return $this->hasMany(CourseContent::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withPivot('role'); // instructor or student
    }

    /**
     * Get instructors for this course through pivot table
     */
    public function instructors(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withPivot('role')->wherePivot('role', 'instructor');
    }

    /**
     * Get enrollments for this course (alias for students relationship)
     */
    public function enrollments(): BelongsToMany
    {
        return $this->students();
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(ClassSession::class);
    }

    public function exams(): HasMany
    {
        return $this->hasMany(Exam::class);
    }

    public function studentProgress(): HasMany
    {
        return $this->hasMany(StudentProgress::class);
    }

    public function students(): BelongsToMany
    {
        return $this->users()->wherePivot('role', 'student');
    }

    /**
     * Get the parent course
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'parent_id');
    }
    
    /**
     * Get children (modules, chapters, lessons)
     */
    public function children(): HasMany
    {
        return $this->hasMany(Course::class, 'parent_id')->orderBy('position');
    }
    
    /**
     * Get only modules (first level children)
     */
    public function modules(): HasMany
    {
        return $this->hasMany(Course::class, 'parent_id')
                    ->where('content_type', 'module')
                    ->orderBy('position');
    }
    
    /**
     * Get only chapters (can be direct children or through modules)
     */
    public function chapters(): HasMany
    {
        return $this->hasMany(Course::class, 'parent_id')
                    ->where('content_type', 'chapter')
                    ->orderBy('position');
    }
    
    /**
     * Recursive method to get entire course tree
     */
    public function getTree()
    {
        return $this->children()->with(['children' => function($query) {
            $query->orderBy('position');
        }])->orderBy('position')->get();
    }
}
