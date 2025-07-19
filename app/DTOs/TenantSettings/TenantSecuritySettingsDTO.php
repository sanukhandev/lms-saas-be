<?php

namespace App\DTOs\TenantSettings;

class TenantSecuritySettingsDTO
{
    public function __construct(
        public readonly bool $requireEmailVerification,
        public readonly bool $enableTwoFactor,
        public readonly int $passwordMinLength,
        public readonly bool $passwordRequireUppercase,
        public readonly bool $passwordRequireLowercase,
        public readonly bool $passwordRequireNumbers,
        public readonly bool $passwordRequireSymbols,
        public readonly int $sessionTimeout,
        public readonly int $maxLoginAttempts,
        public readonly int $lockoutDuration,
        public readonly array $allowedDomains,
        public readonly array $blockedDomains
    ) {}

    public function toArray(): array
    {
        return [
            'require_email_verification' => $this->requireEmailVerification,
            'enable_two_factor' => $this->enableTwoFactor,
            'password_min_length' => $this->passwordMinLength,
            'password_require_uppercase' => $this->passwordRequireUppercase,
            'password_require_lowercase' => $this->passwordRequireLowercase,
            'password_require_numbers' => $this->passwordRequireNumbers,
            'password_require_symbols' => $this->passwordRequireSymbols,
            'session_timeout' => $this->sessionTimeout,
            'max_login_attempts' => $this->maxLoginAttempts,
            'lockout_duration' => $this->lockoutDuration,
            'allowed_domains' => $this->allowedDomains,
            'blocked_domains' => $this->blockedDomains
        ];
    }
}
