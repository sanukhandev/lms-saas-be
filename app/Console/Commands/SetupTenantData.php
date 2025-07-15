<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class SetupTenantData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lms:setup-tenant-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create initial tenant data with super admin and tenant admins';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Setting up LMS Multi-Tenant Data...');

        // Create Super Admin
        $this->info('Creating Super Admin...');
        $superAdmin = User::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)->updateOrCreate(
            ['email' => 'superadmin@lms.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password123'),
                'role' => 'super_admin',
                'tenant_id' => null,
                'email_verified_at' => now(),
            ]
        );
        $this->info("✓ Super Admin created: {$superAdmin->email}");

        // Create Tenant 1
        $this->info('Creating Tenant 1...');
        $tenant1 = Tenant::updateOrCreate(
            ['domain' => 'acme-university'],
            [
                'name' => 'Acme University',
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
                        'primary_color' => '#3b82f6',
                        'secondary_color' => '#64748b',
                    ],
                ],
            ]
        );
        $this->info("✓ Tenant 1 created: {$tenant1->name} (domain: {$tenant1->domain})");

        // Create Tenant Admin for Tenant 1
        $this->info('Creating Tenant Admin for Tenant 1...');
        $tenantAdmin1 = User::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)->updateOrCreate(
            ['email' => 'admin@acme-university.com'],
            [
                'name' => 'John Doe',
                'password' => Hash::make('password123'),
                'role' => 'admin',
                'tenant_id' => $tenant1->id,
                'email_verified_at' => now(),
            ]
        );
        $this->info("✓ Tenant Admin created: {$tenantAdmin1->email} for {$tenant1->name}");

        // Create Tenant 2
        $this->info('Creating Tenant 2...');
        $tenant2 = Tenant::updateOrCreate(
            ['domain' => 'tech-academy'],
            [
                'name' => 'Tech Academy',
                'settings' => [
                    'timezone' => 'UTC',
                    'language' => 'en',
                    'theme' => 'dark',
                    'features' => [
                        'courses' => true,
                        'certificates' => false,
                        'payments' => true,
                        'notifications' => true,
                    ],
                    'branding' => [
                        'logo' => null,
                        'primary_color' => '#10b981',
                        'secondary_color' => '#6b7280',
                    ],
                ],
            ]
        );
        $this->info("✓ Tenant 2 created: {$tenant2->name} (domain: {$tenant2->domain})");

        // Create Tenant Admin for Tenant 2
        $this->info('Creating Tenant Admin for Tenant 2...');
        $tenantAdmin2 = User::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)->updateOrCreate(
            ['email' => 'admin@tech-academy.com'],
            [
                'name' => 'Jane Smith',
                'password' => Hash::make('password123'),
                'role' => 'admin',
                'tenant_id' => $tenant2->id,
                'email_verified_at' => now(),
            ]
        );
        $this->info("✓ Tenant Admin created: {$tenantAdmin2->email} for {$tenant2->name}");

        // Create additional test users
        $this->info('Creating additional test users...');
        
        // Staff for Tenant 1
        $staff1 = User::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)->updateOrCreate(
            ['email' => 'alice@acme-university.com'],
            [
                'name' => 'Alice Johnson',
                'password' => Hash::make('password123'),
                'role' => 'staff',
                'tenant_id' => $tenant1->id,
                'email_verified_at' => now(),
            ]
        );
        $this->info("✓ Staff created: {$staff1->email}");

        // Tutor for Tenant 1
        $tutor1 = User::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)->updateOrCreate(
            ['email' => 'bob@acme-university.com'],
            [
                'name' => 'Bob Wilson',
                'password' => Hash::make('password123'),
                'role' => 'tutor',
                'tenant_id' => $tenant1->id,
                'email_verified_at' => now(),
            ]
        );
        $this->info("✓ Tutor created: {$tutor1->email}");

        // Student for Tenant 1
        $student1 = User::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)->updateOrCreate(
            ['email' => 'charlie@student.acme-university.com'],
            [
                'name' => 'Charlie Brown',
                'password' => Hash::make('password123'),
                'role' => 'student',
                'tenant_id' => $tenant1->id,
                'email_verified_at' => now(),
            ]
        );
        $this->info("✓ Student created: {$student1->email}");

        $this->info('');
        $this->info('=== Setup Complete! ===');
        $this->info('Login Credentials (all passwords: password123):');
        $this->info('1. Super Admin: superadmin@lms.com');
        $this->info('2. Tenant 1 Admin: admin@acme-university.com');
        $this->info('3. Tenant 2 Admin: admin@tech-academy.com');
        $this->info('4. Staff: alice@acme-university.com');
        $this->info('5. Tutor: bob@acme-university.com');
        $this->info('6. Student: charlie@student.acme-university.com');
        
        $this->info('');
        $this->info('Database Summary:');
        $this->info('Total Tenants: ' . Tenant::count());
        $this->info('Total Users: ' . User::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)->count());
        $this->info('Super Admins: ' . User::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)->where('role', 'super_admin')->count());
        $this->info('Tenant Admins: ' . User::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)->where('role', 'admin')->count());
        $this->info('Staff: ' . User::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)->where('role', 'staff')->count());
        $this->info('Tutors: ' . User::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)->where('role', 'tutor')->count());
        $this->info('Students: ' . User::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)->where('role', 'student')->count());
    }
}
