<?php

namespace App\DTOs\Dashboard;

class UserProgressDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $email,
        public readonly string $avatar,
        public readonly int $enrolledCourses,
        public readonly int $completedCourses,
        public readonly float $totalProgress,
        public readonly string $lastActivity,
        public readonly string $role,
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'avatar' => $this->avatar,
            'enrolledCourses' => $this->enrolledCourses,
            'completedCourses' => $this->completedCourses,
            'totalProgress' => $this->totalProgress,
            'lastActivity' => $this->lastActivity,
            'role' => $this->role,
        ];
    }
}
