<?php

namespace App\DTOs\TenantSettings;

class TenantThemeSettingsDTO
{
    public function __construct(
        public readonly string $primaryColor,
        public readonly string $secondaryColor,
        public readonly string $accentColor,
        public readonly string $backgroundColor,
        public readonly string $textColor,
        public readonly string $fontFamily,
        public readonly string $fontSize,
        public readonly string $borderRadius,
        public readonly string $themeMode,
        public readonly ?string $customCss
    ) {}

    public function toArray(): array
    {
        return [
            'primary_color' => $this->primaryColor,
            'secondary_color' => $this->secondaryColor,
            'accent_color' => $this->accentColor,
            'background_color' => $this->backgroundColor,
            'text_color' => $this->textColor,
            'font_family' => $this->fontFamily,
            'font_size' => $this->fontSize,
            'border_radius' => $this->borderRadius,
            'theme_mode' => $this->themeMode,
            'custom_css' => $this->customCss
        ];
    }
}
