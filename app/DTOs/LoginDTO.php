<?php

namespace App\DTOs;

class LoginDTO
{
    public function __construct(
        public readonly string $email,
        public readonly string $password,
        public readonly string $tenantSlug,
    ) {}

    public function toArray(): array
    {
        return [
            'email' => $this->email,
            'password' => $this->password,
            'tenant_slug' => $this->tenantSlug,
        ];
    }
}
