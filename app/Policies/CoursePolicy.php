<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Course;

class CoursePolicy
{
    public function view(User $user, Course $course): bool
    {
        return $user->role === 'super_admin' || $user->tenant_id === $course->tenant_id;
    }

    public function update(User $user, Course $course): bool
    {
        return $user->role === 'super_admin' || $user->tenant_id === $course->tenant_id;
    }

    public function delete(User $user, Course $course): bool
    {
        return $user->role === 'super_admin' || $user->tenant_id === $course->tenant_id;
    }
} 