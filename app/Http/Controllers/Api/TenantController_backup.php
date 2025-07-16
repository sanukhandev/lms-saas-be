<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\Request;

class TenantController extends Controller
{
    /**
     * Get tenant by domain
     */
    public function getByDomain(Request $request, string $domain)
    {
        $tenant = Tenant::where('domain', $domain)->first();

        if (!$tenant) {
            return response()->json([
                'message' => 'Tenant not found',
                'error' => "No tenant found with domain: {$domain}"
            ], 404);
        }

        return response()->json([
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'domain' => $tenant->domain,
                'settings' => $tenant->settings ?: [
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
                        'mode' => 'light', // light, dark, auto
                        'colors' => [
                            'primary' => '#3b82f6',
                            'secondary' => '#64748b',
                            'accent' => '#8b5cf6',
                            'background' => '#ffffff',
                            'foreground' => '#0f172a',
                            'card' => '#ffffff',
                            'card_foreground' => '#0f172a',
                            'popover' => '#ffffff',
                            'popover_foreground' => '#0f172a',
                            'muted' => '#f1f5f9',
                            'muted_foreground' => '#64748b',
                            'border' => '#e2e8f0',
                            'input' => '#e2e8f0',
                            'ring' => '#3b82f6',
                            'destructive' => '#ef4444',
                            'destructive_foreground' => '#ffffff',
                            'success' => '#10b981',
                            'success_foreground' => '#ffffff',
                            'warning' => '#f59e0b',
                            'warning_foreground' => '#ffffff',
                            'info' => '#3b82f6',
                            'info_foreground' => '#ffffff',
                        ],
                        'typography' => [
                            'font_family' => 'Inter, system-ui, -apple-system, sans-serif',
                            'font_sizes' => [
                                'xs' => '0.75rem',
                                'sm' => '0.875rem',
                                'base' => '1rem',
                                'lg' => '1.125rem',
                                'xl' => '1.25rem',
                                '2xl' => '1.5rem',
                                '3xl' => '1.875rem',
                                '4xl' => '2.25rem',
                                '5xl' => '3rem',
                                '6xl' => '3.75rem',
                            ],
                            'line_heights' => [
                                'none' => '1',
                                'tight' => '1.25',
                                'snug' => '1.375',
                                'normal' => '1.5',
                                'relaxed' => '1.625',
                                'loose' => '2',
                            ],
                            'font_weights' => [
                                'thin' => '100',
                                'light' => '300',
                                'normal' => '400',
                                'medium' => '500',
                                'semibold' => '600',
                                'bold' => '700',
                                'extrabold' => '800',
                                'black' => '900',
                            ],
                        ],
                        'border_radius' => [
                            'none' => '0',
                            'sm' => '0.125rem',
                            'default' => '0.25rem',
                            'md' => '0.375rem',
                            'lg' => '0.5rem',
                            'xl' => '0.75rem',
                            '2xl' => '1rem',
                            '3xl' => '1.5rem',
                            'full' => '9999px',
                        ],
                        'shadows' => [
                            'sm' => '0 1px 2px 0 rgb(0 0 0 / 0.05)',
                            'default' => '0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1)',
                            'md' => '0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1)',
                            'lg' => '0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1)',
                            'xl' => '0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1)',
                            '2xl' => '0 25px 50px -12px rgb(0 0 0 / 0.25)',
                            'inner' => 'inset 0 2px 4px 0 rgb(0 0 0 / 0.05)',
                            'none' => 'none',
                        ],
                        'spacing' => [
                            'xs' => '0.25rem',
                            'sm' => '0.5rem',
                            'md' => '1rem',
                            'lg' => '1.5rem',
                            'xl' => '2rem',
                            '2xl' => '3rem',
                            '3xl' => '4rem',
                            '4xl' => '6rem',
                            '5xl' => '8rem',
                        ],
                    ],
                ],
            ]
        ]);
    }

    /**
     * Get all tenants (for super admin)
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        if (!$user || $user->role !== 'super_admin') {
            return response()->json([
                'message' => 'Unauthorized. Super admin access required.',
            ], 403);
        }

        $tenants = Tenant::all()->map(function ($tenant) {
            return [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'domain' => $tenant->domain,
                'settings' => $tenant->settings,
                'created_at' => $tenant->created_at,
                'updated_at' => $tenant->updated_at,
            ];
        });

        return response()->json([
            'tenants' => $tenants
        ]);
    }

    /**
     * Update tenant settings
     */
    public function updateSettings(Request $request, string $domain)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $tenant = Tenant::where('domain', $domain)->first();

        if (!$tenant) {
            return response()->json([
                'message' => 'Tenant not found',
                'error' => "No tenant found with domain: {$domain}"
            ], 404);
        }

        // Check if user has permission to update this tenant
        if ($user->role !== 'super_admin' && $user->tenant_id !== $tenant->id) {
            return response()->json([
                'message' => 'Unauthorized. You can only update your own tenant settings.',
            ], 403);
        }

        $request->validate([
            'settings' => 'required|array',
            'settings.timezone' => 'sometimes|string',
            'settings.language' => 'sometimes|string',
            'settings.theme' => 'sometimes|string',
            'settings.features' => 'sometimes|array',
            'settings.branding' => 'sometimes|array',
            'settings.theme_config' => 'sometimes|array',
            'settings.theme_config.mode' => 'sometimes|string|in:light,dark,auto',
            'settings.theme_config.colors' => 'sometimes|array',
            'settings.theme_config.typography' => 'sometimes|array',
            'settings.theme_config.border_radius' => 'sometimes|array',
            'settings.theme_config.shadows' => 'sometimes|array',
            'settings.theme_config.spacing' => 'sometimes|array',
        ]);

        $tenant->settings = array_merge($tenant->settings ?: [], $request->settings);
        $tenant->save();

        return response()->json([
            'message' => 'Tenant settings updated successfully',
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'domain' => $tenant->domain,
                'settings' => $tenant->settings,
            ]
        ]);
    }

    /**
     * Get current tenant info based on user context
     */
    public function current(Request $request)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        if ($user->role === 'super_admin') {
            // Super admin can access all tenants
            $tenants = Tenant::all();
            return response()->json([
                'message' => 'Super admin access - all tenants available',
                'tenants' => $tenants->map(function ($tenant) {
                    return [
                        'id' => $tenant->id,
                        'name' => $tenant->name,
                        'domain' => $tenant->domain,
                        'settings' => $tenant->settings,
                    ];
                }),
                'current_tenant' => null // Super admin doesn't have a fixed tenant
            ]);
        }

        if (!$user->tenant_id) {
            return response()->json([
                'message' => 'User not associated with any tenant',
                'error' => 'User tenant_id is null'
            ], 400);
        }

        $tenant = $user->tenant;

        return response()->json([
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'domain' => $tenant->domain,
                'settings' => $tenant->settings,
            ]
        ]);
    }
}
