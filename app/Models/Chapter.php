<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Chapter extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'module_id',
        'title',
        'description',
        'position',
        'duration_minutes',
        'video_url',
        'content',
        'learning_objectives',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'position' => 'integer',
        'duration_minutes' => 'float',
        'learning_objectives' => 'json',
    ];

    /**
     * Get the module that this chapter belongs to
     */
    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }
}
