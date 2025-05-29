<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentPayment extends Model
{
    protected $fillable = [
        'tenant_id',
        'student_id',
        'invoice_id',
        'class_session_id',
        'amount',
        'currency',
        'status',
        'payment_method',
        'transaction_ref'
    ];

    public function tenant():BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function student():BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function invoice():BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function session():BelongsTo
    {
        return $this->belongsTo(ClassSession::class);
    }
}
