<?php

namespace App\Services\TenantSettings;

use App\DTOs\TenantSettings\TenantGeneralSettingsDTO;
use App\DTOs\TenantSettings\TenantBrandingSettingsDTO;
use App\DTOs\TenantSettings\TenantFeaturesSettingsDTO;
use App\DTOs\TenantSettings\TenantSecuritySettingsDTO;
use App\DTOs\TenantSettings\TenantThemeSettingsDTO;
use App\Models\Tenant;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class TenantSettingsService
{
    /**
     * Get tenant general settings with caching
     */
    public function getGeneralSettings(): array
    {
        $tenantId = Auth::user()->tenant_id;
        $cacheKey = "tenant_general_settings_{$tenantId}";
        
        return Cache::remember($cacheKey, 1800, function () use ($tenantId) { // 30 minutes
            try {
                $tenant = Tenant::find($tenantId);
                
                if (!$tenant) {
                    return ['success' => false, 'message' => 'Tenant not found'];
                }

                $settingsDTO = new TenantGeneralSettingsDTO(
                    $tenant->id,
                    $tenant->name,
                    $tenant->domain,
                    $tenant->description,
                    $tenant->status,
                    $tenant->settings['timezone'] ?? 'UTC',
                    $tenant->settings['language'] ?? 'en',
                    $tenant->settings['date_format'] ?? 'Y-m-d',
                    $tenant->settings['time_format'] ?? 'H:i:s',
                    $tenant->settings['currency'] ?? 'USD',
                    $tenant->settings['max_users'] ?? 100,
                    $tenant->settings['max_courses'] ?? 50,
                    $tenant->settings['storage_limit'] ?? 1000,
                    $tenant->created_at,
                    $tenant->updated_at
                );

                return [
                    'success' => true,
                    'data' => $settingsDTO->toArray()
                ];

            } catch (\Exception $e) {
                Log::error('Error fetching tenant general settings', [
                    'tenant_id' => $tenantId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                
                return ['success' => false, 'message' => 'Failed to fetch general settings'];
            }
        });
    }

    /**
     * Update tenant general settings
     */
    public function updateGeneralSettings(array $data): array
    {
        $tenantId = Auth::user()->tenant_id;
        
        try {
            $tenant = Tenant::find($tenantId);
            
            if (!$tenant) {
                return ['success' => false, 'message' => 'Tenant not found'];
            }

            DB::beginTransaction();

            $settings = $tenant->settings ?? [];
            $settings = array_merge($settings, [
                'timezone' => $data['timezone'],
                'language' => $data['language'],
                'date_format' => $data['date_format'],
                'time_format' => $data['time_format'],
                'currency' => $data['currency'],
                'max_users' => $data['max_users'],
                'max_courses' => $data['max_courses'],
                'storage_limit' => $data['storage_limit'],
            ]);

            $tenant->update([
                'name' => $data['name'],
                'domain' => $data['domain'],
                'description' => $data['description'] ?? null,
                'settings' => $settings
            ]);

            DB::commit();

            // Clear related caches
            $this->clearTenantCaches($tenantId, 'general');

            // Return updated settings
            return $this->getGeneralSettings();

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error updating tenant general settings', [
                'tenant_id' => $tenantId,
                'data' => $data,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return ['success' => false, 'message' => 'Failed to update general settings'];
        }
    }

    /**
     * Get tenant branding settings with caching
     */
    public function getBrandingSettings(): array
    {
        $tenantId = Auth::user()->tenant_id;
        $cacheKey = "tenant_branding_settings_{$tenantId}";
        
        return Cache::remember($cacheKey, 1800, function () use ($tenantId) {
            try {
                $tenant = Tenant::find($tenantId);
                
                if (!$tenant) {
                    return ['success' => false, 'message' => 'Tenant not found'];
                }

                $brandingDTO = new TenantBrandingSettingsDTO(
                    $tenant->settings['logo_url'] ?? null,
                    $tenant->settings['favicon_url'] ?? null,
                    $tenant->settings['brand_name'] ?? $tenant->name,
                    $tenant->settings['brand_tagline'] ?? null,
                    $tenant->settings['brand_description'] ?? null,
                    $tenant->settings['contact_email'] ?? null,
                    $tenant->settings['contact_phone'] ?? null,
                    $tenant->settings['contact_address'] ?? null,
                    $tenant->settings['social_links'] ?? []
                );

                return [
                    'success' => true,
                    'data' => $brandingDTO->toArray()
                ];

            } catch (\Exception $e) {
                Log::error('Error fetching tenant branding settings', [
                    'tenant_id' => $tenantId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                
                return ['success' => false, 'message' => 'Failed to fetch branding settings'];
            }
        });
    }

    /**
     * Update tenant branding settings
     */
    public function updateBrandingSettings(array $data): array
    {
        $tenantId = Auth::user()->tenant_id;
        
        try {
            $tenant = Tenant::find($tenantId);
            
            if (!$tenant) {
                return ['success' => false, 'message' => 'Tenant not found'];
            }

            DB::beginTransaction();

            $settings = $tenant->settings ?? [];

            // Handle file uploads
            if (!empty($data['logo_file'])) {
                // Delete old logo
                if (!empty($settings['logo_url'])) {
                    $oldPath = str_replace('/storage/', '', $settings['logo_url']);
                    Storage::disk('public')->delete($oldPath);
                }

                $logoPath = $data['logo_file']->store('tenant-branding/' . $tenantId, 'public');
                $settings['logo_url'] = Storage::url($logoPath);
            }

            if (!empty($data['favicon_file'])) {
                // Delete old favicon
                if (!empty($settings['favicon_url'])) {
                    $oldPath = str_replace('/storage/', '', $settings['favicon_url']);
                    Storage::disk('public')->delete($oldPath);
                }

                $faviconPath = $data['favicon_file']->store('tenant-branding/' . $tenantId, 'public');
                $settings['favicon_url'] = Storage::url($faviconPath);
            }

            // Update other branding settings
            $settings = array_merge($settings, [
                'brand_name' => $data['brand_name'] ?? $tenant->name,
                'brand_tagline' => $data['brand_tagline'] ?? null,
                'brand_description' => $data['brand_description'] ?? null,
                'contact_email' => $data['contact_email'] ?? null,
                'contact_phone' => $data['contact_phone'] ?? null,
                'contact_address' => $data['contact_address'] ?? null,
                'social_links' => $data['social_links'] ?? []
            ]);

            $tenant->update(['settings' => $settings]);

            DB::commit();

            // Clear related caches
            $this->clearTenantCaches($tenantId, 'branding');

            return $this->getBrandingSettings();

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error updating tenant branding settings', [
                'tenant_id' => $tenantId,
                'data' => array_diff_key($data, ['logo_file' => '', 'favicon_file' => '']), // Don't log files
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return ['success' => false, 'message' => 'Failed to update branding settings'];
        }
    }

    /**
     * Get tenant features settings with caching
     */
    public function getFeaturesSettings(): array
    {
        $tenantId = Auth::user()->tenant_id;
        $cacheKey = "tenant_features_settings_{$tenantId}";
        
        return Cache::remember($cacheKey, 1800, function () use ($tenantId) {
            try {
                $tenant = Tenant::find($tenantId);
                
                if (!$tenant) {
                    return ['success' => false, 'message' => 'Tenant not found'];
                }

                $featuresDTO = new TenantFeaturesSettingsDTO(
                    $tenant->settings['enable_course_creation'] ?? true,
                    $tenant->settings['enable_user_registration'] ?? true,
                    $tenant->settings['enable_course_reviews'] ?? true,
                    $tenant->settings['enable_discussions'] ?? true,
                    $tenant->settings['enable_certificates'] ?? false,
                    $tenant->settings['enable_analytics'] ?? true,
                    $tenant->settings['enable_notifications'] ?? true,
                    $tenant->settings['enable_file_uploads'] ?? true,
                    $tenant->settings['enable_video_streaming'] ?? false,
                    $tenant->settings['enable_live_sessions'] ?? false,
                    $tenant->settings['max_file_size'] ?? 10,
                    $tenant->settings['allowed_file_types'] ?? ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'mp4', 'mp3']
                );

                return [
                    'success' => true,
                    'data' => $featuresDTO->toArray()
                ];

            } catch (\Exception $e) {
                Log::error('Error fetching tenant features settings', [
                    'tenant_id' => $tenantId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                
                return ['success' => false, 'message' => 'Failed to fetch features settings'];
            }
        });
    }

    /**
     * Update tenant features settings
     */
    public function updateFeaturesSettings(array $data): array
    {
        $tenantId = Auth::user()->tenant_id;
        
        try {
            $tenant = Tenant::find($tenantId);
            
            if (!$tenant) {
                return ['success' => false, 'message' => 'Tenant not found'];
            }

            DB::beginTransaction();

            $settings = $tenant->settings ?? [];
            $settings = array_merge($settings, [
                'enable_course_creation' => $data['enable_course_creation'] ?? true,
                'enable_user_registration' => $data['enable_user_registration'] ?? true,
                'enable_course_reviews' => $data['enable_course_reviews'] ?? true,
                'enable_discussions' => $data['enable_discussions'] ?? true,
                'enable_certificates' => $data['enable_certificates'] ?? false,
                'enable_analytics' => $data['enable_analytics'] ?? true,
                'enable_notifications' => $data['enable_notifications'] ?? true,
                'enable_file_uploads' => $data['enable_file_uploads'] ?? true,
                'enable_video_streaming' => $data['enable_video_streaming'] ?? false,
                'enable_live_sessions' => $data['enable_live_sessions'] ?? false,
                'max_file_size' => $data['max_file_size'] ?? 10,
                'allowed_file_types' => $data['allowed_file_types'] ?? ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'mp4', 'mp3']
            ]);

            $tenant->update(['settings' => $settings]);

            DB::commit();

            // Clear related caches
            $this->clearTenantCaches($tenantId, 'features');

            return $this->getFeaturesSettings();

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error updating tenant features settings', [
                'tenant_id' => $tenantId,
                'data' => $data,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return ['success' => false, 'message' => 'Failed to update features settings'];
        }
    }

    /**
     * Get tenant security settings with caching
     */
    public function getSecuritySettings(): array
    {
        $tenantId = Auth::user()->tenant_id;
        $cacheKey = "tenant_security_settings_{$tenantId}";
        
        return Cache::remember($cacheKey, 1800, function () use ($tenantId) {
            try {
                $tenant = Tenant::find($tenantId);
                
                if (!$tenant) {
                    return ['success' => false, 'message' => 'Tenant not found'];
                }

                $securityDTO = new TenantSecuritySettingsDTO(
                    $tenant->settings['require_email_verification'] ?? true,
                    $tenant->settings['enable_two_factor'] ?? false,
                    $tenant->settings['password_min_length'] ?? 8,
                    $tenant->settings['password_require_uppercase'] ?? true,
                    $tenant->settings['password_require_lowercase'] ?? true,
                    $tenant->settings['password_require_numbers'] ?? true,
                    $tenant->settings['password_require_symbols'] ?? false,
                    $tenant->settings['session_timeout'] ?? 120,
                    $tenant->settings['max_login_attempts'] ?? 5,
                    $tenant->settings['lockout_duration'] ?? 15,
                    $tenant->settings['allowed_domains'] ?? [],
                    $tenant->settings['blocked_domains'] ?? []
                );

                return [
                    'success' => true,
                    'data' => $securityDTO->toArray()
                ];

            } catch (\Exception $e) {
                Log::error('Error fetching tenant security settings', [
                    'tenant_id' => $tenantId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                
                return ['success' => false, 'message' => 'Failed to fetch security settings'];
            }
        });
    }

    /**
     * Update tenant security settings
     */
    public function updateSecuritySettings(array $data): array
    {
        $tenantId = Auth::user()->tenant_id;
        
        try {
            $tenant = Tenant::find($tenantId);
            
            if (!$tenant) {
                return ['success' => false, 'message' => 'Tenant not found'];
            }

            DB::beginTransaction();

            $settings = $tenant->settings ?? [];
            $settings = array_merge($settings, [
                'require_email_verification' => $data['require_email_verification'] ?? true,
                'enable_two_factor' => $data['enable_two_factor'] ?? false,
                'password_min_length' => $data['password_min_length'] ?? 8,
                'password_require_uppercase' => $data['password_require_uppercase'] ?? true,
                'password_require_lowercase' => $data['password_require_lowercase'] ?? true,
                'password_require_numbers' => $data['password_require_numbers'] ?? true,
                'password_require_symbols' => $data['password_require_symbols'] ?? false,
                'session_timeout' => $data['session_timeout'] ?? 120,
                'max_login_attempts' => $data['max_login_attempts'] ?? 5,
                'lockout_duration' => $data['lockout_duration'] ?? 15,
                'allowed_domains' => $data['allowed_domains'] ?? [],
                'blocked_domains' => $data['blocked_domains'] ?? []
            ]);

            $tenant->update(['settings' => $settings]);

            DB::commit();

            // Clear related caches
            $this->clearTenantCaches($tenantId, 'security');

            return $this->getSecuritySettings();

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error updating tenant security settings', [
                'tenant_id' => $tenantId,
                'data' => $data,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return ['success' => false, 'message' => 'Failed to update security settings'];
        }
    }

    /**
     * Get tenant theme settings with caching
     */
    public function getThemeSettings(): array
    {
        $tenantId = Auth::user()->tenant_id;
        $cacheKey = "tenant_theme_settings_{$tenantId}";
        
        return Cache::remember($cacheKey, 1800, function () use ($tenantId) {
            try {
                $tenant = Tenant::find($tenantId);
                
                if (!$tenant) {
                    return ['success' => false, 'message' => 'Tenant not found'];
                }

                $themeDTO = new TenantThemeSettingsDTO(
                    $tenant->settings['primary_color'] ?? '#3b82f6',
                    $tenant->settings['secondary_color'] ?? '#64748b',
                    $tenant->settings['accent_color'] ?? '#f59e0b',
                    $tenant->settings['background_color'] ?? '#ffffff',
                    $tenant->settings['text_color'] ?? '#1f2937',
                    $tenant->settings['font_family'] ?? 'Inter',
                    $tenant->settings['font_size'] ?? 'medium',
                    $tenant->settings['border_radius'] ?? 'medium',
                    $tenant->settings['theme_mode'] ?? 'light',
                    $tenant->settings['custom_css'] ?? null
                );

                return [
                    'success' => true,
                    'data' => $themeDTO->toArray()
                ];

            } catch (\Exception $e) {
                Log::error('Error fetching tenant theme settings', [
                    'tenant_id' => $tenantId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                
                return ['success' => false, 'message' => 'Failed to fetch theme settings'];
            }
        });
    }

    /**
     * Update tenant theme settings
     */
    public function updateThemeSettings(array $data): array
    {
        $tenantId = Auth::user()->tenant_id;
        
        try {
            $tenant = Tenant::find($tenantId);
            
            if (!$tenant) {
                return ['success' => false, 'message' => 'Tenant not found'];
            }

            DB::beginTransaction();

            $settings = $tenant->settings ?? [];
            $settings = array_merge($settings, [
                'primary_color' => $data['primary_color'] ?? '#3b82f6',
                'secondary_color' => $data['secondary_color'] ?? '#64748b',
                'accent_color' => $data['accent_color'] ?? '#f59e0b',
                'background_color' => $data['background_color'] ?? '#ffffff',
                'text_color' => $data['text_color'] ?? '#1f2937',
                'font_family' => $data['font_family'] ?? 'Inter',
                'font_size' => $data['font_size'] ?? 'medium',
                'border_radius' => $data['border_radius'] ?? 'medium',
                'theme_mode' => $data['theme_mode'] ?? 'light',
                'custom_css' => $data['custom_css'] ?? null
            ]);

            $tenant->update(['settings' => $settings]);

            DB::commit();

            // Clear related caches
            $this->clearTenantCaches($tenantId, 'theme');

            return $this->getThemeSettings();

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error updating tenant theme settings', [
                'tenant_id' => $tenantId,
                'data' => $data,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return ['success' => false, 'message' => 'Failed to update theme settings'];
        }
    }

    /**
     * Get color palettes
     */
    public function getColorPalettes(): array
    {
        return [
            'success' => true,
            'data' => [
                'default' => [
                    'name' => 'Default Blue',
                    'primary' => '#3b82f6',
                    'secondary' => '#64748b',
                    'accent' => '#f59e0b'
                ],
                'emerald' => [
                    'name' => 'Emerald',
                    'primary' => '#10b981',
                    'secondary' => '#6b7280',
                    'accent' => '#f59e0b'
                ],
                'purple' => [
                    'name' => 'Purple',
                    'primary' => '#8b5cf6',
                    'secondary' => '#6b7280',
                    'accent' => '#f59e0b'
                ],
                'rose' => [
                    'name' => 'Rose',
                    'primary' => '#f43f5e',
                    'secondary' => '#6b7280',
                    'accent' => '#f59e0b'
                ],
                'orange' => [
                    'name' => 'Orange',
                    'primary' => '#f97316',
                    'secondary' => '#6b7280',
                    'accent' => '#3b82f6'
                ]
            ]
        ];
    }

    /**
     * Get preset themes
     */
    public function getPresetThemes(): array
    {
        return [
            'success' => true,
            'data' => [
                'modern' => [
                    'name' => 'Modern',
                    'settings' => [
                        'primary_color' => '#3b82f6',
                        'secondary_color' => '#64748b',
                        'accent_color' => '#f59e0b',
                        'background_color' => '#ffffff',
                        'text_color' => '#1f2937',
                        'font_family' => 'Inter',
                        'font_size' => 'medium',
                        'border_radius' => 'medium'
                    ]
                ],
                'minimal' => [
                    'name' => 'Minimal',
                    'settings' => [
                        'primary_color' => '#000000',
                        'secondary_color' => '#6b7280',
                        'accent_color' => '#3b82f6',
                        'background_color' => '#ffffff',
                        'text_color' => '#111827',
                        'font_family' => 'Inter',
                        'font_size' => 'small',
                        'border_radius' => 'small'
                    ]
                ],
                'vibrant' => [
                    'name' => 'Vibrant',
                    'settings' => [
                        'primary_color' => '#8b5cf6',
                        'secondary_color' => '#f43f5e',
                        'accent_color' => '#10b981',
                        'background_color' => '#fafafa',
                        'text_color' => '#1f2937',
                        'font_family' => 'Poppins',
                        'font_size' => 'medium',
                        'border_radius' => 'large'
                    ]
                ]
            ]
        ];
    }

    /**
     * Clear tenant related caches
     */
    private function clearTenantCaches(int $tenantId, ?string $type = null): void
    {
        try {
            if ($type) {
                Cache::forget("tenant_{$type}_settings_{$tenantId}");
            } else {
                // Clear all tenant settings caches
                $types = ['general', 'branding', 'features', 'security', 'theme'];
                foreach ($types as $settingType) {
                    Cache::forget("tenant_{$settingType}_settings_{$tenantId}");
                }
            }

        } catch (\Exception $e) {
            Log::error('Error clearing tenant caches', [
                'tenant_id' => $tenantId,
                'type' => $type,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
