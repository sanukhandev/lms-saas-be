<?php

namespace App\DTOs\ClassSchedule;

class TeachingPlanDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $courseId,
        public readonly ?string $contentId,
        public readonly string $instructorId,
        public readonly ?string $instructorName,
        public readonly ?string $contentTitle,
        public readonly string $classType,
        public readonly string $plannedDate,
        public readonly int $durationMins,
        public readonly ?string $learningObjectives,
        public readonly ?string $prerequisites,
        public readonly ?string $materialsNeeded,
        public readonly ?string $notes,
        public readonly int $priority,
        public readonly bool $isFlexible,
        public readonly string $status,
        public readonly string $createdAt,
        public readonly string $updatedAt
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'courseId' => $this->courseId,
            'contentId' => $this->contentId,
            'instructorId' => $this->instructorId,
            'instructorName' => $this->instructorName,
            'contentTitle' => $this->contentTitle,
            'classType' => $this->classType,
            'plannedDate' => $this->plannedDate,
            'durationMins' => $this->durationMins,
            'learningObjectives' => $this->learningObjectives,
            'prerequisites' => $this->prerequisites,
            'materialsNeeded' => $this->materialsNeeded,
            'notes' => $this->notes,
            'priority' => $this->priority,
            'isFlexible' => $this->isFlexible,
            'status' => $this->status,
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt,
        ];
    }
}
