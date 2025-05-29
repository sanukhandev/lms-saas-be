<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseMaterial extends Model
{
    protected $fillable = [
        'tenant_id',
        'course_id',
        'content_id',
        'title',
        'description',
        'type',
        'file_path',
        'external_url',
        'is_public'
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
}
