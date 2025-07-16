<?php

namespace App\DTOs\Tenant;

class UpdateTenantSettingsDTO
{
    public function __construct(
        public readonly array $settings,
        public readonly ?string $timezone = null,
        public readonly ?string $language = null,
        public readonly ?string $theme = null,
        public readonly ?array $features = null,
        public readonly ?array $branding = null,
        public readonly ?array $themeConfig = null,
    ) {}

    public static function fromRequest(array $data): self
    {
        $settings = $data['settings'];

        return new self(
            settings: $settings,
            timezone: $settings['timezone'] ?? null,
            language: $settings['language'] ?? null,
            theme: $settings['theme'] ?? null,
            features: $settings['features'] ?? null,
            branding: $settings['branding'] ?? null,
            themeConfig: $settings['theme_config'] ?? null,
        );
    }
}
