<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamQuestion extends Model
{

    protected $fillable = [
        'exam_id',
        'question',
        'options',
        'correct_answer',
        'marks'
    ];

    protected $casts = [
        'options' => 'array'
    ];

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }
}
