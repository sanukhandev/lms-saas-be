<?php

namespace App\DTOs\Session;

class AttendanceDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $sessionId,
        public readonly string $studentId,
        public readonly ?string $studentName,
        public readonly string $status,
        public readonly ?string $joinedAt,
        public readonly ?string $leftAt,
        public readonly ?string $notes,
        public readonly string $createdAt,
        public readonly string $updatedAt
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'sessionId' => $this->sessionId,
            'studentId' => $this->studentId,
            'studentName' => $this->studentName,
            'status' => $this->status,
            'joinedAt' => $this->joinedAt,
            'leftAt' => $this->leftAt,
            'notes' => $this->notes,
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt,
        ];
    }
}
