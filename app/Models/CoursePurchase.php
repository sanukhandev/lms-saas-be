<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CoursePurchase extends Model
{
    use \App\Traits\BelongsToTenant, HasFactory;
    protected $fillable = [
        'tenant_id',
        'student_id',
        'course_id',
        'amount_paid',
        'currency',
        'invoice_id',
        'access_start_date',
        'access_expires_at',
        'is_active'
    ];

    public function tenant():BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function student():BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
