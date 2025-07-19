<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use BelongsToTenant, HasFactory;
    
    protected $fillable = [
        'tenant_id', 
        'name', 
        'slug', 
        'parent_id',
        'description',
        'is_active',
        'sort_order',
        'image_url',
        'meta_description'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function tenant():BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function parent():BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children():HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function courses():HasMany
    {
        return $this->hasMany(Course::class);
    }
}
