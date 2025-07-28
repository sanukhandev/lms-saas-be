<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function view(User $user, User $model): bool
    {
        return $user->role === 'super_admin' || $user->tenant_id === $model->tenant_id;
    }

    public function update(User $user, User $model): bool
    {
        return $user->role === 'super_admin' || $user->tenant_id === $model->tenant_id;
    }

    public function delete(User $user, User $model): bool
    {
        return $user->role === 'super_admin' || $user->tenant_id === $model->tenant_id;
    }
} 