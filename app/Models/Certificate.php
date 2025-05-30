<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Certificate extends Model
{
    use BelongsToTenant;
    protected $fillable = [
        'tenant_id',
        'course_id',
        'student_id',
        'exam_result_id',
        'certificate_no',
        'template_slug',
        'pdf_path',
        'is_verified'
    ];

    public function tenant():BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function result(): BelongsTo
    {
        return $this->belongsTo(ExamResult::class, 'exam_result_id');
    }
}
