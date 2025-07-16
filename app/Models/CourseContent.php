<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourseContent extends Model
{
    use BelongsToTenant, HasFactory;
    protected $fillable = [
        'course_id',
        'parent_id',
        'type',
        'title',
        'description',
        'position',
        'duration_mins',
        'tenant_id'
    ];

    public function course():BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function parent():BelongsTo
    {
        return $this->belongsTo(CourseContent::class, 'parent_id');
    }

    public function children():HasMany
    {
        return $this->hasMany(CourseContent::class, 'parent_id');
    }

    public function sessions():HasMany
    {
        return $this->hasMany(ClassSession::class, 'content_id');
    }
}
