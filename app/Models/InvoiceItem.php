<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{

    protected $fillable = [
        'invoice_id',
        'class_session_id',
        'description',
        'quantity',
        'unit_price'
    ];
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(ClassSession::class);
    }
}
