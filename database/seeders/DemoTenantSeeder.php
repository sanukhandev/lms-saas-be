<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Tenant;

class DemoTenantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create demo tenant if it doesn't exist
        $demoTenant = Tenant::where('domain', 'demo')->first();
        
        if (!$demoTenant) {
            $demoTenant = Tenant::create([
                'name' => 'Demo Tenant',
                'domain' => 'demo',
                'slug' => 'demo',
                'settings' => [
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
                        'primary_color' => '#2563eb',
                        'secondary_color' => '#64748b',
                        'company_name' => 'Demo Company',
                        'favicon' => null,
                    ],
                    'theme_config' => [
                        'mode' => 'light',
                        'colors' => [
                            'primary' => '#2563eb',
                            'secondary' => '#64748b',
                            'accent' => '#7c3aed',
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
                            'ring' => '#2563eb',
                            'destructive' => '#dc2626',
                            'destructive_foreground' => '#ffffff',
                            'success' => '#059669',
                            'success_foreground' => '#ffffff',
                            'warning' => '#d97706',
                            'warning_foreground' => '#ffffff',
                            'info' => '#2563eb',
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
            ]);
            
            $this->command->info("Created demo tenant: {$demoTenant->name}");
        } else {
            $this->command->info("Demo tenant already exists");
        }
    }
}
