<?php

namespace App\DTOs\Dashboard;

class ActivityDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $type,
        public readonly string $message,
        public readonly string $timestamp,
        public readonly UserDTO $user,
        public readonly array $metadata = [],
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'message' => $this->message,
            'timestamp' => $this->timestamp,
            'user' => $this->user->toArray(),
            'metadata' => $this->metadata,
        ];
    }
}

class UserDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly string $avatar,
    ) {}

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'avatar' => $this->avatar,
        ];
    }
}
