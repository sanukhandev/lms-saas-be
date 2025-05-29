<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstructorPayout extends Model
{
    protected $fillable = [
        'tenant_id',
        'instructor_id',
        'class_session_id',
        'amount',
        'status',
        'paid_at'
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(ClassSession::class);
    }
}
