<?php

namespace App\DTOs\User;

class UserListDTO
{
    public function __construct(
        public readonly array $users,
        public readonly int $total,
        public readonly int $currentPage,
        public readonly int $perPage,
        public readonly int $lastPage,
    ) {}

    public function toArray(): array
    {
        return [
            'users' => array_map(fn($user) => $user->toArray(), $this->users),
            'pagination' => [
                'total' => $this->total,
                'current_page' => $this->currentPage,
                'per_page' => $this->perPage,
                'last_page' => $this->lastPage,
            ],
        ];
    }
}
