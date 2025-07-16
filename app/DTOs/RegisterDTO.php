<?php

namespace App\DTOs;

class RegisterDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly string $password,
        public readonly string $tenantSlug,
        public readonly string $role = 'student',
    ) {}

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'password' => $this->password,
            'tenant_slug' => $this->tenantSlug,
            'role' => $this->role,
        ];
    }
}
