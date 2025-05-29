<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstructorAttendance extends Model
{
    protected $fillable = [
        'class_session_id',
        'instructor_id',
        'checked_in_at',
        'checked_out_at',
        'location',
        'status'
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(ClassSession::class);
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }
}
