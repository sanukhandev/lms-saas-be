<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentConfig extends Model
{
    use \App\Traits\BelongsToTenant;
    protected $fillable = [
        'tenant_id',
        'mode',
        'default_session_rate',
        'enable_student_payment',
        'enable_instructor_payout'
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

}
