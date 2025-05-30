<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Exam extends Model
{
    use \App\Traits\BelongsToTenant;
    protected $fillable = [
        'tenant_id',
        'course_id',
        'content_id',
        'title',
        'instructions',
        'is_published',
    ];

    public function tenant():BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function course():BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function content():BelongsTo
    {
        return $this->belongsTo(CourseContent::class, 'content_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(ExamQuestion::class);
    }

    public function results(): HasMany
    {
        return $this->hasMany(ExamResult::class);
    }
}
