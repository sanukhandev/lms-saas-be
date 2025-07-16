<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ClassSession extends Model
{
    use BelongsToTenant, HasFactory;
    protected $fillable = [
        'tenant_id',
        'course_id',
        'content_id',
        'tutor_id',
        'scheduled_at',
        'duration_mins',
        'meeting_url',
        'is_recorded',
        'recording_url',
        'status'
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

    public function tutor():BelongsTo
    {
        return $this->belongsTo(User::class, 'tutor_id');
    }

    public function students():BelongsToMany
    {
        return $this->belongsToMany(User::class, 'session_user')
            ->withPivot('attendance_status', 'joined_at', 'left_at', 'location')
            ->withTimestamps();
    }
}
