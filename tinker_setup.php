<?php

/**
 * PHP Tinker Script to Create Tenant, Tenant Admin, and Super Admin
 * 
 * Run this in Laravel Tinker: php artisan tinker
 * Then copy and paste the code below, or run: require_once 'tinker_setup.php';
 */

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

echo "=== LMS Multi-Tenant System Setup ===\n\n";

echo "Models Structure:\n";
echo "1. Tenant Model:\n";
echo "   - id (primary key)\n";
echo "   - name (string)\n";
echo "   - domain (string, unique)\n";
echo "   - settings (json)\n";
echo "   - timestamps\n\n";

echo "2. User Model:\n";
echo "   - id (primary key)\n";
echo "   - tenant_id (foreign key to tenants table)\n";
echo "   - name (string)\n";
echo "   - email (string)\n";
echo "   - role (enum: 'admin', 'staff', 'tutor', 'student', 'super_admin')\n";
echo "   - password (hashed)\n";
echo "   - email_verified_at (timestamp)\n";
echo "   - timestamps\n\n";

echo "3. Relationships:\n";
echo "   - Tenant hasMany Users\n";
echo "   - User belongsTo Tenant\n";
echo "   - User uses BelongsToTenant trait for multi-tenancy\n\n";

echo "4. Multi-Tenancy Features:\n";
echo "   - TenantScope: Automatically filters queries by tenant_id\n";
echo "   - BelongsToTenant trait: Auto-assigns tenant_id on model creation\n";
echo "   - Super admin role bypasses tenant restrictions\n\n";

echo "Creating data...\n\n";

// Create Super Admin (not tied to any tenant)
echo "Creating Super Admin...\n";
try {
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
    echo "✓ Super Admin created: {$superAdmin->email}\n";
} catch (Exception $e) {
    echo "✗ Error creating Super Admin: {$e->getMessage()}\n";
}

// Create Tenant 1
echo "\nCreating Tenant 1...\n";
try {
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
    echo "✓ Tenant 1 created: {$tenant1->name} (domain: {$tenant1->domain})\n";
} catch (Exception $e) {
    echo "✗ Error creating Tenant 1: {$e->getMessage()}\n";
}

// Create Tenant Admin for Tenant 1
echo "\nCreating Tenant Admin for Tenant 1...\n";
try {
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
    echo "✓ Tenant Admin created: {$tenantAdmin1->email} for {$tenant1->name}\n";
} catch (Exception $e) {
    echo "✗ Error creating Tenant Admin: {$e->getMessage()}\n";
}

// Create Tenant 2 for testing
echo "\nCreating Tenant 2...\n";
try {
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
    echo "✓ Tenant 2 created: {$tenant2->name} (domain: {$tenant2->domain})\n";
} catch (Exception $e) {
    echo "✗ Error creating Tenant 2: {$e->getMessage()}\n";
}

// Create Tenant Admin for Tenant 2
echo "\nCreating Tenant Admin for Tenant 2...\n";
try {
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
    echo "✓ Tenant Admin created: {$tenantAdmin2->email} for {$tenant2->name}\n";
} catch (Exception $e) {
    echo "✗ Error creating Tenant Admin 2: {$e->getMessage()}\n";
}

// Create some additional users for testing
echo "\nCreating additional test users...\n";

// Staff for Tenant 1
try {
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
    echo "✓ Staff created: {$staff1->email}\n";
} catch (Exception $e) {
    echo "✗ Error creating Staff: {$e->getMessage()}\n";
}

// Tutor for Tenant 1
try {
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
    echo "✓ Tutor created: {$tutor1->email}\n";
} catch (Exception $e) {
    echo "✗ Error creating Tutor: {$e->getMessage()}\n";
}

// Student for Tenant 1
try {
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
    echo "✓ Student created: {$student1->email}\n";
} catch (Exception $e) {
    echo "✗ Error creating Student: {$e->getMessage()}\n";
}

echo "\n=== Setup Complete! ===\n";
echo "Login Credentials (all passwords: password123):\n";
echo "1. Super Admin: superadmin@lms.com\n";
echo "2. Tenant 1 Admin: admin@acme-university.com\n";
echo "3. Tenant 2 Admin: admin@tech-academy.com\n";
echo "4. Staff: alice@acme-university.com\n";
echo "5. Tutor: bob@acme-university.com\n";
echo "6. Student: charlie@student.acme-university.com\n";

echo "\nTesting Multi-Tenancy:\n";
echo "- Super Admin can access all tenants\n";
echo "- Tenant Admins can only access their own tenant data\n";
echo "- Use X-Tenant-ID header for API requests\n";
echo "- Domain-based routing: acme-university.lms.com vs tech-academy.lms.com\n";

echo "\nDatabase Verification:\n";
echo "Total Tenants: " . Tenant::count() . "\n";
echo "Total Users: " . User::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)->count() . "\n";
echo "Super Admins: " . User::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)->where('role', 'super_admin')->count() . "\n";
echo "Tenant Admins: " . User::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)->where('role', 'admin')->count() . "\n";
echo "Staff: " . User::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)->where('role', 'staff')->count() . "\n";
echo "Tutors: " . User::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)->where('role', 'tutor')->count() . "\n";
echo "Students: " . User::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)->where('role', 'student')->count() . "\n";

echo "\nNext Steps:\n";
echo "1. Run: php artisan migrate (if not already done)\n";
echo "2. Run: php artisan db:seed --class=TenantAndAdminSeeder\n";
echo "3. Test API endpoints with different tenant contexts\n";
echo "4. Configure frontend to send X-Tenant-ID header\n";
echo "5. Set up subdomain routing if needed\n";
