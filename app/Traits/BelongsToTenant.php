<?php

namespace App\Traits;

use App\Models\Scopes\TenantScope;

trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope());

        static::creating(function ($model) {
            $user = auth()->user();
            if ($user && $user->role !== 'super_admin') {
                $model->tenant_id = $model->tenant_id ?? $user->tenant_id;
            } elseif (empty($model->tenant_id)) {
                $model->tenant_id = Request::header('X-Tenant-ID');
            }
        });
    }
}
