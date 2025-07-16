<?php

namespace App\DTOs\Auth;

class RegisterDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly string $password,
        public readonly ?int $tenantId = null,
        public readonly string $role = 'student',
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            name: $data['name'],
            email: $data['email'],
            password: $data['password'],
            tenantId: $data['tenant_id'] ?? null,
            role: $data['role'] ?? 'student',
        );
    }
}
