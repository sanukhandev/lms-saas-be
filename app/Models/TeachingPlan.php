<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeachingPlan extends Model
{
    protected $fillable = [
        'tenant_id',
        'course_id',
        'content_id',
        'instructor_id',
        'class_type',
        'planned_date',
        'duration_mins'
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function content(): BelongsTo
    {
        return $this->belongsTo(CourseContent::class, 'content_id');
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }
}
