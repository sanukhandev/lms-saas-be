<?php

namespace App\DTOs\Auth;

use App\Models\User;

class AuthResponseDTO
{
    public function __construct(
        public readonly User $user,
        public readonly string $token,
        public readonly string $tokenType = 'Bearer',
        public readonly ?string $message = null,
    ) {}

    public function toArray(): array
    {
        $data = [
            'user' => $this->user->load('tenant'),
            'token' => $this->token,
            'token_type' => $this->tokenType,
        ];

        if ($this->message) {
            $data['message'] = $this->message;
        }

        return $data;
    }
}
