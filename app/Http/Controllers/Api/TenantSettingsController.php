<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class TenantSettingsController extends Controller
{
    use ApiResponseTrait;

    /**
     * Get tenant general settings
     */
    public function getGeneralSettings()
    {
        $tenant = Auth::user()->tenant;

        return $this->successResponse([
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'domain' => $tenant->domain,
                'description' => $tenant->description,
                'status' => $tenant->status,
                'timezone' => $tenant->settings['timezone'] ?? 'UTC',
                'language' => $tenant->settings['language'] ?? 'en',
                'date_format' => $tenant->settings['date_format'] ?? 'Y-m-d',
                'time_format' => $tenant->settings['time_format'] ?? 'H:i',
                'currency' => $tenant->settings['currency'] ?? 'USD',
                'max_users' => $tenant->settings['max_users'] ?? 1000,
                'max_courses' => $tenant->settings['max_courses'] ?? 100,
                'storage_limit' => $tenant->settings['storage_limit'] ?? 10240, // MB
                'created_at' => $tenant->created_at,
                'updated_at' => $tenant->updated_at,
            ]
        ], 'General settings retrieved successfully');
    }

    /**
     * Update tenant general settings
     */
    public function updateGeneralSettings(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'domain' => 'required|string|max:255|unique:tenants,domain,' . Auth::user()->tenant->id,
            'description' => 'nullable|string|max:1000',
            'timezone' => 'required|string|max:50',
            'language' => 'required|string|max:10',
            'date_format' => 'required|string|max:20',
            'time_format' => 'required|string|max:20',
            'currency' => 'required|string|max:3',
            'max_users' => 'required|integer|min:1',
            'max_courses' => 'required|integer|min:1',
            'storage_limit' => 'required|integer|min:100',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        $tenant = Auth::user()->tenant;

        $tenant->update([
            'name' => $request->name,
            'domain' => $request->domain,
            'description' => $request->description,
            'settings' => array_merge($tenant->settings ?? [], [
                'timezone' => $request->timezone,
                'language' => $request->language,
                'date_format' => $request->date_format,
                'time_format' => $request->time_format,
                'currency' => $request->currency,
                'max_users' => $request->max_users,
                'max_courses' => $request->max_courses,
                'storage_limit' => $request->storage_limit,
            ])
        ]);

        return $this->successResponse([
            'tenant' => $tenant->fresh()
        ], 'General settings updated successfully');
    }

    /**
     * Get tenant branding settings
     */
    public function getBrandingSettings()
    {
        $tenant = Auth::user()->tenant;
        $branding = $tenant->settings['branding'] ?? [];

        return $this->successResponse([
            'branding' => [
                'logo' => $branding['logo'] ?? null,
                'favicon' => $branding['favicon'] ?? null,
                'company_name' => $branding['company_name'] ?? $tenant->name,
                'primary_color' => $branding['primary_color'] ?? '#3b82f6',
                'secondary_color' => $branding['secondary_color'] ?? '#64748b',
                'accent_color' => $branding['accent_color'] ?? '#10b981',
                'background_color' => $branding['background_color'] ?? '#ffffff',
                'text_color' => $branding['text_color'] ?? '#1f2937',
                'footer_text' => $branding['footer_text'] ?? null,
                'welcome_message' => $branding['welcome_message'] ?? null,
                'email_signature' => $branding['email_signature'] ?? null,
            ]
        ], 'Branding settings retrieved successfully');
    }

    /**
     * Update tenant branding settings
     */
    public function updateBrandingSettings(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'favicon' => 'nullable|image|mimes:ico,png,jpg,gif,svg|max:512',
            'company_name' => 'required|string|max:255',
            'primary_color' => 'required|string|regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/',
            'secondary_color' => 'required|string|regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/',
            'accent_color' => 'required|string|regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/',
            'background_color' => 'required|string|regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/',
            'text_color' => 'required|string|regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/',
            'footer_text' => 'nullable|string|max:500',
            'welcome_message' => 'nullable|string|max:1000',
            'email_signature' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        $tenant = Auth::user()->tenant;
        $branding = $tenant->settings['branding'] ?? [];

        // Handle logo upload
        if ($request->hasFile('logo')) {
            if (isset($branding['logo']) && $branding['logo']) {
                Storage::disk('public')->delete($branding['logo']);
            }
            $logoPath = $request->file('logo')->store('tenant-logos', 'public');
            $branding['logo'] = $logoPath;
        }

        // Handle favicon upload
        if ($request->hasFile('favicon')) {
            if (isset($branding['favicon']) && $branding['favicon']) {
                Storage::disk('public')->delete($branding['favicon']);
            }
            $faviconPath = $request->file('favicon')->store('tenant-favicons', 'public');
            $branding['favicon'] = $faviconPath;
        }

        // Update branding settings
        $branding = array_merge($branding, [
            'company_name' => $request->company_name,
            'primary_color' => $request->primary_color,
            'secondary_color' => $request->secondary_color,
            'accent_color' => $request->accent_color,
            'background_color' => $request->background_color,
            'text_color' => $request->text_color,
            'footer_text' => $request->footer_text,
            'welcome_message' => $request->welcome_message,
            'email_signature' => $request->email_signature,
        ]);

        $tenant->update([
            'settings' => array_merge($tenant->settings ?? [], [
                'branding' => $branding
            ])
        ]);

        return $this->successResponse([
            'branding' => $branding
        ], 'Branding settings updated successfully');
    }

    /**
     * Get tenant features settings
     */
    public function getFeaturesSettings()
    {
        $tenant = Auth::user()->tenant;
        $features = $tenant->settings['features'] ?? [];

        return $this->successResponse([
            'features' => [
                'courses' => $features['courses'] ?? true,
                'certificates' => $features['certificates'] ?? true,
                'payments' => $features['payments'] ?? false,
                'notifications' => $features['notifications'] ?? true,
                'messaging' => $features['messaging'] ?? true,
                'forums' => $features['forums'] ?? false,
                'live_sessions' => $features['live_sessions'] ?? false,
                'mobile_app' => $features['mobile_app'] ?? false,
                'analytics' => $features['analytics'] ?? true,
                'api_access' => $features['api_access'] ?? false,
                'white_label' => $features['white_label'] ?? false,
                'custom_domain' => $features['custom_domain'] ?? false,
                'sso' => $features['sso'] ?? false,
                'ldap' => $features['ldap'] ?? false,
                'backup' => $features['backup'] ?? true,
            ]
        ], 'Features settings retrieved successfully');
    }

    /**
     * Update tenant features settings
     */
    public function updateFeaturesSettings(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'courses' => 'required|boolean',
            'certificates' => 'required|boolean',
            'payments' => 'required|boolean',
            'notifications' => 'required|boolean',
            'messaging' => 'required|boolean',
            'forums' => 'required|boolean',
            'live_sessions' => 'required|boolean',
            'mobile_app' => 'required|boolean',
            'analytics' => 'required|boolean',
            'api_access' => 'required|boolean',
            'white_label' => 'required|boolean',
            'custom_domain' => 'required|boolean',
            'sso' => 'required|boolean',
            'ldap' => 'required|boolean',
            'backup' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        $tenant = Auth::user()->tenant;

        $features = [
            'courses' => $request->courses,
            'certificates' => $request->certificates,
            'payments' => $request->payments,
            'notifications' => $request->notifications,
            'messaging' => $request->messaging,
            'forums' => $request->forums,
            'live_sessions' => $request->live_sessions,
            'mobile_app' => $request->mobile_app,
            'analytics' => $request->analytics,
            'api_access' => $request->api_access,
            'white_label' => $request->white_label,
            'custom_domain' => $request->custom_domain,
            'sso' => $request->sso,
            'ldap' => $request->ldap,
            'backup' => $request->backup,
        ];

        $tenant->update([
            'settings' => array_merge($tenant->settings ?? [], [
                'features' => $features
            ])
        ]);

        return $this->successResponse([
            'features' => $features
        ], 'Features settings updated successfully');
    }

    /**
     * Get tenant security settings
     */
    public function getSecuritySettings()
    {
        $tenant = Auth::user()->tenant;
        $security = $tenant->settings['security'] ?? [];

        return $this->successResponse([
            'security' => [
                'two_factor_auth' => $security['two_factor_auth'] ?? false,
                'password_policy' => $security['password_policy'] ?? [
                    'min_length' => 8,
                    'require_uppercase' => true,
                    'require_lowercase' => true,
                    'require_numbers' => true,
                    'require_symbols' => false,
                    'password_history' => 5,
                    'password_expiry' => 90,
                ],
                'session_timeout' => $security['session_timeout'] ?? 30, // minutes
                'max_login_attempts' => $security['max_login_attempts'] ?? 5,
                'lockout_duration' => $security['lockout_duration'] ?? 15, // minutes
                'ip_whitelist' => $security['ip_whitelist'] ?? [],
                'allowed_domains' => $security['allowed_domains'] ?? [],
                'force_ssl' => $security['force_ssl'] ?? true,
                'content_security_policy' => $security['content_security_policy'] ?? true,
                'data_retention_days' => $security['data_retention_days'] ?? 365,
                'audit_logging' => $security['audit_logging'] ?? true,
                'backup_encryption' => $security['backup_encryption'] ?? true,
            ]
        ], 'Security settings retrieved successfully');
    }

    /**
     * Update tenant security settings
     */
    public function updateSecuritySettings(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'two_factor_auth' => 'required|boolean',
            'password_policy' => 'required|array',
            'password_policy.min_length' => 'required|integer|min:6|max:128',
            'password_policy.require_uppercase' => 'required|boolean',
            'password_policy.require_lowercase' => 'required|boolean',
            'password_policy.require_numbers' => 'required|boolean',
            'password_policy.require_symbols' => 'required|boolean',
            'password_policy.password_history' => 'required|integer|min:0|max:24',
            'password_policy.password_expiry' => 'required|integer|min:0|max:365',
            'session_timeout' => 'required|integer|min:5|max:480',
            'max_login_attempts' => 'required|integer|min:3|max:10',
            'lockout_duration' => 'required|integer|min:5|max:60',
            'ip_whitelist' => 'nullable|array',
            'ip_whitelist.*' => 'ip',
            'allowed_domains' => 'nullable|array',
            'allowed_domains.*' => 'string|max:255',
            'force_ssl' => 'required|boolean',
            'content_security_policy' => 'required|boolean',
            'data_retention_days' => 'required|integer|min:30|max:2555',
            'audit_logging' => 'required|boolean',
            'backup_encryption' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        $tenant = Auth::user()->tenant;

        $security = [
            'two_factor_auth' => $request->two_factor_auth,
            'password_policy' => $request->password_policy,
            'session_timeout' => $request->session_timeout,
            'max_login_attempts' => $request->max_login_attempts,
            'lockout_duration' => $request->lockout_duration,
            'ip_whitelist' => $request->ip_whitelist ?? [],
            'allowed_domains' => $request->allowed_domains ?? [],
            'force_ssl' => $request->force_ssl,
            'content_security_policy' => $request->content_security_policy,
            'data_retention_days' => $request->data_retention_days,
            'audit_logging' => $request->audit_logging,
            'backup_encryption' => $request->backup_encryption,
        ];

        $tenant->update([
            'settings' => array_merge($tenant->settings ?? [], [
                'security' => $security
            ])
        ]);

        return $this->successResponse([
            'security' => $security
        ], 'Security settings updated successfully');
    }

    /**
     * Get tenant theme settings
     */
    public function getThemeSettings()
    {
        $tenant = Auth::user()->tenant;
        $theme_config = $tenant->settings['theme_config'] ?? [];

        return $this->successResponse([
            'theme_config' => $theme_config
        ], 'Theme settings retrieved successfully');
    }

    /**
     * Update tenant theme settings
     */
    public function updateThemeSettings(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'mode' => 'required|in:light,dark,auto',
            'colors' => 'required|array',
            'typography' => 'required|array',
            'border_radius' => 'required|array',
            'shadows' => 'required|array',
            'spacing' => 'required|array',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        $tenant = Auth::user()->tenant;

        $theme_config = $request->all();

        $tenant->update([
            'settings' => array_merge($tenant->settings ?? [], [
                'theme_config' => $theme_config
            ])
        ]);

        return $this->successResponse([
            'theme_config' => $theme_config
        ], 'Theme settings updated successfully');
    }

    /**
     * Get color palettes for theme customization
     */
    public function getColorPalettes()
    {
        $colorPalettes = [
            [
                'id' => 'default',
                'name' => 'Default',
                'description' => 'Default color palette',
                'colors' => [
                    'primary' => '#3b82f6',
                    'secondary' => '#64748b',
                    'accent' => '#10b981',
                    'background' => '#ffffff',
                    'foreground' => '#1f2937',
                    'card' => '#ffffff',
                    'cardForeground' => '#1f2937',
                    'popover' => '#ffffff',
                    'popoverForeground' => '#1f2937',
                    'muted' => '#f8fafc',
                    'mutedForeground' => '#64748b',
                    'border' => '#e2e8f0',
                    'input' => '#e2e8f0',
                    'ring' => '#3b82f6',
                    'destructive' => '#ef4444',
                    'chart1' => '#3b82f6',
                    'chart2' => '#10b981',
                    'chart3' => '#f59e0b',
                    'chart4' => '#ef4444',
                    'chart5' => '#8b5cf6',
                ],
                'darkModeColors' => [
                    'primary' => '#3b82f6',
                    'secondary' => '#64748b',
                    'accent' => '#10b981',
                    'background' => '#0f172a',
                    'foreground' => '#f8fafc',
                    'card' => '#1e293b',
                    'cardForeground' => '#f8fafc',
                    'popover' => '#1e293b',
                    'popoverForeground' => '#f8fafc',
                    'muted' => '#334155',
                    'mutedForeground' => '#94a3b8',
                    'border' => '#334155',
                    'input' => '#334155',
                    'ring' => '#3b82f6',
                    'destructive' => '#ef4444',
                    'chart1' => '#60a5fa',
                    'chart2' => '#34d399',
                    'chart3' => '#fbbf24',
                    'chart4' => '#f87171',
                    'chart5' => '#a78bfa',
                ],
                'preview' => 'linear-gradient(135deg, #3b82f6 0%, #10b981 100%)',
            ],
            [
                'id' => 'ocean',
                'name' => 'Ocean',
                'description' => 'Ocean blue color palette',
                'colors' => [
                    'primary' => '#0ea5e9',
                    'secondary' => '#0f766e',
                    'accent' => '#06b6d4',
                    'background' => '#ffffff',
                    'foreground' => '#1f2937',
                    'card' => '#ffffff',
                    'cardForeground' => '#1f2937',
                    'popover' => '#ffffff',
                    'popoverForeground' => '#1f2937',
                    'muted' => '#f0f9ff',
                    'mutedForeground' => '#64748b',
                    'border' => '#e0f2fe',
                    'input' => '#e0f2fe',
                    'ring' => '#0ea5e9',
                    'destructive' => '#ef4444',
                    'chart1' => '#0ea5e9',
                    'chart2' => '#06b6d4',
                    'chart3' => '#0f766e',
                    'chart4' => '#22d3ee',
                    'chart5' => '#67e8f9',
                ],
                'darkModeColors' => [
                    'primary' => '#0ea5e9',
                    'secondary' => '#0f766e',
                    'accent' => '#06b6d4',
                    'background' => '#0c1825',
                    'foreground' => '#f8fafc',
                    'card' => '#1e293b',
                    'cardForeground' => '#f8fafc',
                    'popover' => '#1e293b',
                    'popoverForeground' => '#f8fafc',
                    'muted' => '#334155',
                    'mutedForeground' => '#94a3b8',
                    'border' => '#334155',
                    'input' => '#334155',
                    'ring' => '#0ea5e9',
                    'destructive' => '#ef4444',
                    'chart1' => '#38bdf8',
                    'chart2' => '#22d3ee',
                    'chart3' => '#14b8a6',
                    'chart4' => '#67e8f9',
                    'chart5' => '#a7f3d0',
                ],
                'preview' => 'linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%)',
            ],
            [
                'id' => 'forest',
                'name' => 'Forest',
                'description' => 'Forest green color palette',
                'colors' => [
                    'primary' => '#22c55e',
                    'secondary' => '#15803d',
                    'accent' => '#84cc16',
                    'background' => '#ffffff',
                    'foreground' => '#1f2937',
                    'card' => '#ffffff',
                    'cardForeground' => '#1f2937',
                    'popover' => '#ffffff',
                    'popoverForeground' => '#1f2937',
                    'muted' => '#f0fdf4',
                    'mutedForeground' => '#64748b',
                    'border' => '#dcfce7',
                    'input' => '#dcfce7',
                    'ring' => '#22c55e',
                    'destructive' => '#ef4444',
                    'chart1' => '#22c55e',
                    'chart2' => '#84cc16',
                    'chart3' => '#15803d',
                    'chart4' => '#4ade80',
                    'chart5' => '#a3e635',
                ],
                'darkModeColors' => [
                    'primary' => '#22c55e',
                    'secondary' => '#15803d',
                    'accent' => '#84cc16',
                    'background' => '#0f1b0f',
                    'foreground' => '#f8fafc',
                    'card' => '#1e293b',
                    'cardForeground' => '#f8fafc',
                    'popover' => '#1e293b',
                    'popoverForeground' => '#f8fafc',
                    'muted' => '#334155',
                    'mutedForeground' => '#94a3b8',
                    'border' => '#334155',
                    'input' => '#334155',
                    'ring' => '#22c55e',
                    'destructive' => '#ef4444',
                    'chart1' => '#4ade80',
                    'chart2' => '#a3e635',
                    'chart3' => '#22c55e',
                    'chart4' => '#16a34a',
                    'chart5' => '#84cc16',
                ],
                'preview' => 'linear-gradient(135deg, #22c55e 0%, #84cc16 100%)',
            ],
        ];

        return $this->successResponse([
            'palettes' => $colorPalettes,
            'categories' => ['Default', 'Nature', 'Corporate', 'Creative'],
        ], 'Color palettes retrieved successfully');
    }

    /**
     * Get preset themes
     */
    public function getPresetThemes()
    {
        $presetThemes = [
            [
                'id' => 'modern',
                'name' => 'Modern',
                'description' => 'Clean and modern design',
                'colors' => [
                    'primary' => '#3b82f6',
                    'secondary' => '#64748b',
                    'accent' => '#10b981',
                    'background' => '#ffffff',
                    'foreground' => '#1f2937',
                ],
                'typography' => [
                    'font_family' => 'Inter',
                    'font_size' => '16px',
                    'line_height' => '1.5',
                ],
                'border_radius' => [
                    'small' => '4px',
                    'medium' => '8px',
                    'large' => '12px',
                ],
                'shadows' => [
                    'small' => '0 1px 2px rgba(0, 0, 0, 0.1)',
                    'medium' => '0 4px 6px rgba(0, 0, 0, 0.1)',
                    'large' => '0 10px 15px rgba(0, 0, 0, 0.1)',
                ],
                'spacing' => [
                    'small' => '8px',
                    'medium' => '16px',
                    'large' => '24px',
                ],
            ],
            [
                'id' => 'corporate',
                'name' => 'Corporate',
                'description' => 'Professional corporate design',
                'colors' => [
                    'primary' => '#1e40af',
                    'secondary' => '#374151',
                    'accent' => '#059669',
                    'background' => '#f9fafb',
                    'foreground' => '#111827',
                ],
                'typography' => [
                    'font_family' => 'Roboto',
                    'font_size' => '14px',
                    'line_height' => '1.4',
                ],
                'border_radius' => [
                    'small' => '2px',
                    'medium' => '4px',
                    'large' => '6px',
                ],
                'shadows' => [
                    'small' => '0 1px 1px rgba(0, 0, 0, 0.1)',
                    'medium' => '0 2px 4px rgba(0, 0, 0, 0.1)',
                    'large' => '0 4px 8px rgba(0, 0, 0, 0.1)',
                ],
                'spacing' => [
                    'small' => '6px',
                    'medium' => '12px',
                    'large' => '18px',
                ],
            ],
            [
                'id' => 'creative',
                'name' => 'Creative',
                'description' => 'Vibrant and creative design',
                'colors' => [
                    'primary' => '#7c3aed',
                    'secondary' => '#f59e0b',
                    'accent' => '#ec4899',
                    'background' => '#ffffff',
                    'foreground' => '#1f2937',
                ],
                'typography' => [
                    'font_family' => 'Poppins',
                    'font_size' => '16px',
                    'line_height' => '1.6',
                ],
                'border_radius' => [
                    'small' => '8px',
                    'medium' => '16px',
                    'large' => '24px',
                ],
                'shadows' => [
                    'small' => '0 2px 4px rgba(124, 58, 237, 0.1)',
                    'medium' => '0 8px 16px rgba(124, 58, 237, 0.1)',
                    'large' => '0 16px 32px rgba(124, 58, 237, 0.1)',
                ],
                'spacing' => [
                    'small' => '12px',
                    'medium' => '20px',
                    'large' => '32px',
                ],
            ],
        ];

        return $this->successResponse([
            'presets' => $presetThemes,
        ], 'Preset themes retrieved successfully');
    }
}
