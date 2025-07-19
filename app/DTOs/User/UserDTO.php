<?php

namespace App\DTOs\User;

use Carbon\Carbon;

class UserDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $email,
        public readonly string $role,
        public readonly string $status,
        public readonly bool $emailVerified,
        public readonly int $enrollmentCount,
        public readonly int $completedCourses,
        public readonly ?Carbon $lastLoginAt,
        public readonly Carbon $createdAt,
        public readonly Carbon $updatedAt,
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'status' => $this->status,
            'email_verified' => $this->emailVerified,
            'enrollment_count' => $this->enrollmentCount,
            'completed_courses' => $this->completedCourses,
            'last_login_at' => $this->lastLoginAt?->toISOString(),
            'created_at' => $this->createdAt->toISOString(),
            'updated_at' => $this->updatedAt->toISOString(),
        ];
    }
}
