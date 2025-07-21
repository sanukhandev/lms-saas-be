<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\BelongsToTenant;

class ExamResult extends Model
{
    use BelongsToTenant, HasFactory;
    protected $fillable = [
        'tenant_id',
        'exam_id',
        'student_id',
        'score',
        'is_passed',
        'answers'
    ];

    protected $casts = [
        'answers' => 'array'
    ];

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
