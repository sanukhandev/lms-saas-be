<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantSetting extends Model
{
    use \App\Traits\BelongsToTenant;
    protected $fillable = [
        'tenant_id',
        'default_class_duration',
        'class_type',
        'enable_certificates',
        'certificate_template',
        'enable_recordings',
        'recording_source',
        'enable_invoices',
        'billing_mode',
        'enable_exams'
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
