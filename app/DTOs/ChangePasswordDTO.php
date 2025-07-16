<?php

namespace App\DTOs;

class ChangePasswordDTO
{
    public function __construct(
        public readonly string $currentPassword,
        public readonly string $newPassword,
        public readonly string $confirmPassword,
    ) {}

    public function toArray(): array
    {
        return [
            'current_password' => $this->currentPassword,
            'new_password' => $this->newPassword,
            'confirm_password' => $this->confirmPassword,
        ];
    }
}
