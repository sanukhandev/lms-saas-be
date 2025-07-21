<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Category;

class CategoryPolicy
{
    public function view(User $user, Category $category): bool
    {
        return $user->role === 'super_admin' || $user->tenant_id === $category->tenant_id;
    }

    public function update(User $user, Category $category): bool
    {
        return $user->role === 'super_admin' || $user->tenant_id === $category->tenant_id;
    }

    public function delete(User $user, Category $category): bool
    {
        return $user->role === 'super_admin' || $user->tenant_id === $category->tenant_id;
    }
} 