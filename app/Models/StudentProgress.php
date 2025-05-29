<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentProgress extends Model
{
    protected $fillable = [
        'tenant_id',
        'student_id',
        'course_id',
        'content_id',
        'status',
        'progress_percent',
        'started_at',
        'completed_at'
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function student():BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function content():BelongsTo
    {
        return $this->belongsTo(CourseContent::class, 'content_id');
    }

    public function course():BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function tenant():BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
