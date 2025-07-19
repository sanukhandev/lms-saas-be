<?php

namespace App\DTOs\TenantSettings;

class TenantGeneralSettingsDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $domain,
        public readonly ?string $description,
        public readonly string $status,
        public readonly string $timezone,
        public readonly string $language,
        public readonly string $dateFormat,
        public readonly string $timeFormat,
        public readonly string $currency,
        public readonly int $maxUsers,
        public readonly int $maxCourses,
        public readonly int $storageLimit,
        public readonly \DateTime $createdAt,
        public readonly \DateTime $updatedAt
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'domain' => $this->domain,
            'description' => $this->description,
            'status' => $this->status,
            'timezone' => $this->timezone,
            'language' => $this->language,
            'date_format' => $this->dateFormat,
            'time_format' => $this->timeFormat,
            'currency' => $this->currency,
            'max_users' => $this->maxUsers,
            'max_courses' => $this->maxCourses,
            'storage_limit' => $this->storageLimit,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt->format('Y-m-d H:i:s')
        ];
    }
}
