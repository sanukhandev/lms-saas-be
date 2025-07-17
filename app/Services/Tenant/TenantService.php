<?php

namespace App\Services\Tenant;

use App\DTOs\Tenant\UpdateTenantSettingsDTO;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class TenantService
{
    /**
     * Find tenant by ID
     */
    public function findById(int $tenantId): ?Tenant
    {
        return Tenant::find($tenantId);
    }

    /**
     * Find tenant by domain
     */
    public function findByDomain(string $domain): ?Tenant
    {
        return Tenant::where('domain', $domain)->first();
    }

    /**
     * Get current tenant for authenticated user
     */
    public function getCurrentTenant(User $user): ?Tenant
    {
        // Super admin doesn't have a specific tenant
        if ($user->role === 'super_admin') {
            return null;
        }

        return $user->tenant;
    }

    /**
     * Get all tenants (super admin only)
     */
    public function getAllTenants(): \Illuminate\Database\Eloquent\Collection
    {
        return Tenant::with('users')->get();
    }

    /**
     * Update tenant settings
     */
    public function updateSettings(Tenant $tenant, UpdateTenantSettingsDTO $dto): Tenant
    {
        try {
            // Merge existing settings with new settings
            $currentSettings = $tenant->settings ?? [];
            $mergedSettings = array_merge($currentSettings, $dto->settings);

            $tenant->update(['settings' => $mergedSettings]);

            Log::info('Tenant settings updated successfully', [
                'tenant_id' => $tenant->id,
                'tenant_domain' => $tenant->domain,
                'updated_settings' => array_keys($dto->settings),
            ]);

            return $tenant->fresh();
        } catch (\Exception $e) {
            Log::error('Failed to update tenant settings', [
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Validate user can access tenant
     */
    public function validateUserAccess(User $user, Tenant $tenant): bool
    {
        // Super admin can access any tenant
        if ($user->role === 'super_admin') {
            return true;
        }

        // Admin/staff can only access their own tenant
        if (in_array($user->role, ['admin', 'staff'])) {
            return $user->tenant_id === $tenant->id;
        }

        // Other roles (tutor, student) can only access their own tenant
        return $user->tenant_id === $tenant->id;
    }

    /**
     * Get tenant with default settings structure
     */
    public function getTenantWithDefaultSettings(string $domain): array
    {
        $tenant = $this->findByDomain($domain);

        if (!$tenant) {
            return [];
        }

        // Ensure all required settings keys exist with defaults
        $defaultSettings = [
            'timezone' => 'UTC',
            'language' => 'en',
            'theme' => 'default',
            'features' => [
                'courses' => true,
                'certificates' => true,
                'payments' => true,
                'notifications' => true,
            ],
            'branding' => [
                'logo' => null,
                'primary_color' => '#3b82f6',
                'secondary_color' => '#64748b',
                'company_name' => $tenant->name,
                'favicon' => null,
            ],
            'theme_config' => [
                'mode' => 'light',
                'colors' => [
                    'primary' => '#3b82f6',
                    'secondary' => '#64748b',
                    'accent' => '#8b5cf6',
                    'background' => '#ffffff',
                    'foreground' => '#0f172a',
                ],
            ],
        ];

        // Handle settings casting - ensure it's an array
        $tenantSettings = [];
        if ($tenant->settings) {
            if (is_string($tenant->settings)) {
                // Try to decode JSON if it's a string
                $decoded = json_decode($tenant->settings, true);
                $tenantSettings = is_array($decoded) ? $decoded : [];
            } elseif (is_array($tenant->settings)) {
                $tenantSettings = $tenant->settings;
            }
        }

        $settings = array_merge($defaultSettings, $tenantSettings);

        return [
            'id' => $tenant->id,
            'name' => $tenant->name,
            'domain' => $tenant->domain,
            'settings' => $settings,
        ];
    }
}
