<?php

namespace App\DTOs\TenantSettings;

class TenantBrandingSettingsDTO
{
    public function __construct(
        public readonly ?string $logoUrl,
        public readonly ?string $faviconUrl,
        public readonly string $brandName,
        public readonly ?string $brandTagline,
        public readonly ?string $brandDescription,
        public readonly ?string $contactEmail,
        public readonly ?string $contactPhone,
        public readonly ?string $contactAddress,
        public readonly array $socialLinks
    ) {}

    public function toArray(): array
    {
        return [
            'logo_url' => $this->logoUrl,
            'favicon_url' => $this->faviconUrl,
            'brand_name' => $this->brandName,
            'brand_tagline' => $this->brandTagline,
            'brand_description' => $this->brandDescription,
            'contact_email' => $this->contactEmail,
            'contact_phone' => $this->contactPhone,
            'contact_address' => $this->contactAddress,
            'social_links' => $this->socialLinks
        ];
    }
}
