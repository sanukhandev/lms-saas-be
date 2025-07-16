<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentProgress extends Model
{
    use \App\Traits\BelongsToTenant;
    protected $fillable = [
        'tenant_id',
        'user_id',
        'course_id',
        'content_id',
        'status',
        'completion_percentage',
        'time_spent_mins',
        'last_accessed',
        'started_at',
        'completed_at'
    ];

    protected $casts = [
        'last_accessed' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function student():BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function user():BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
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
