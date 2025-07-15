<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TenantAndAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Super Admin (not tied to any tenant)
        $superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@lms.com',
            'password' => Hash::make('password123'),
            'role' => 'super_admin',
            'tenant_id' => null, // Super admin is not tied to any tenant
            'email_verified_at' => now(),
        ]);

        // Create Tenant 1
        $tenant1 = Tenant::create([
            'name' => 'Acme University',
            'domain' => 'acme-university',
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
        ]);

        // Create Tenant 2 (for testing)
        $tenant2 = Tenant::create([
            'name' => 'Tech Academy',
            'domain' => 'tech-academy',
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
        ]);

        // Create Tenant Admin for Tenant 1
        $tenantAdmin1 = User::create([
            'name' => 'John Doe',
            'email' => 'admin@acme-university.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'tenant_id' => $tenant1->id,
            'email_verified_at' => now(),
        ]);

        // Create Tenant Admin for Tenant 2
        $tenantAdmin2 = User::create([
            'name' => 'Jane Smith',
            'email' => 'admin@tech-academy.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'tenant_id' => $tenant2->id,
            'email_verified_at' => now(),
        ]);

        // Create some staff members for Tenant 1
        $staff1 = User::create([
            'name' => 'Alice Johnson',
            'email' => 'alice@acme-university.com',
            'password' => Hash::make('password123'),
            'role' => 'staff',
            'tenant_id' => $tenant1->id,
            'email_verified_at' => now(),
        ]);

        // Create some tutors for Tenant 1
        $tutor1 = User::create([
            'name' => 'Bob Wilson',
            'email' => 'bob@acme-university.com',
            'password' => Hash::make('password123'),
            'role' => 'tutor',
            'tenant_id' => $tenant1->id,
            'email_verified_at' => now(),
        ]);

        // Create some students for Tenant 1
        $student1 = User::create([
            'name' => 'Charlie Brown',
            'email' => 'charlie@student.acme-university.com',
            'password' => Hash::make('password123'),
            'role' => 'student',
            'tenant_id' => $tenant1->id,
            'email_verified_at' => now(),
        ]);

        $student2 = User::create([
            'name' => 'Diana Prince',
            'email' => 'diana@student.acme-university.com',
            'password' => Hash::make('password123'),
            'role' => 'student',
            'tenant_id' => $tenant1->id,
            'email_verified_at' => now(),
        ]);

        // Create some users for Tenant 2
        $tutor2 = User::create([
            'name' => 'Eve Taylor',
            'email' => 'eve@tech-academy.com',
            'password' => Hash::make('password123'),
            'role' => 'tutor',
            'tenant_id' => $tenant2->id,
            'email_verified_at' => now(),
        ]);

        $student3 = User::create([
            'name' => 'Frank Miller',
            'email' => 'frank@student.tech-academy.com',
            'password' => Hash::make('password123'),
            'role' => 'student',
            'tenant_id' => $tenant2->id,
            'email_verified_at' => now(),
        ]);

        $this->command->info('Created the following users:');
        $this->command->info('Super Admin: superadmin@lms.com (password: password123)');
        $this->command->info('Tenant 1 Admin: admin@acme-university.com (password: password123)');
        $this->command->info('Tenant 2 Admin: admin@tech-academy.com (password: password123)');
        $this->command->info('Staff: alice@acme-university.com (password: password123)');
        $this->command->info('Tutor 1: bob@acme-university.com (password: password123)');
        $this->command->info('Tutor 2: eve@tech-academy.com (password: password123)');
        $this->command->info('Student 1: charlie@student.acme-university.com (password: password123)');
        $this->command->info('Student 2: diana@student.acme-university.com (password: password123)');
        $this->command->info('Student 3: frank@student.tech-academy.com (password: password123)');
    }
}
