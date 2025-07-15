# LMS Multi-Tenant System Documentation

## Models Structure

### 1. Tenant Model
- **Table**: `tenants`
- **Fields**:
  - `id` (Primary Key)
  - `name` (String) - Tenant name
  - `domain` (String, Unique) - Tenant domain/slug
  - `settings` (JSON) - Tenant configuration settings
  - `created_at`, `updated_at` (Timestamps)

### 2. User Model
- **Table**: `users`
- **Fields**:
  - `id` (Primary Key)
  - `tenant_id` (Foreign Key) - References tenants.id
  - `name` (String) - User's full name
  - `email` (String) - User's email address
  - `role` (Enum) - User role: 'admin', 'staff', 'tutor', 'student', 'super_admin'
  - `password` (Hashed String) - User's password
  - `email_verified_at` (Timestamp) - Email verification timestamp
  - `created_at`, `updated_at` (Timestamps)

### 3. Relationships
- **Tenant**: `hasMany(User::class)`
- **User**: `belongsTo(Tenant::class)`

## Multi-Tenancy Features

### 1. Tenant Scope
- Automatically filters all queries by `tenant_id`
- Located in: `app/Models/Scopes/TenantScope.php`
- Bypassed for super_admin users

### 2. BelongsToTenant Trait
- Automatically assigns `tenant_id` when creating models
- Located in: `app/Traits/BelongsToTenant.php`
- Uses authenticated user's tenant_id or X-Tenant-ID header

### 3. User Roles
- **super_admin**: Can access all tenants, bypasses tenant scope
- **admin**: Tenant administrator with full access to their tenant
- **staff**: Staff member with limited admin access
- **tutor**: Instructor/teacher role
- **student**: Student role with limited access

## Database Setup

### 1. Run Migrations
```bash
php artisan migrate
```

### 2. Create Initial Data (Choose one method):

#### Method A: Using Artisan Command
```bash
php artisan lms:setup-tenant-data
```

#### Method B: Using Seeder
```bash
php artisan db:seed --class=TenantAndAdminSeeder
```

#### Method C: Using PHP Tinker
```bash
php artisan tinker
```

Then run the tinker script:
```php
require_once 'tinker_setup.php';
```

Or copy and paste the commands from the tinker script manually.

## Default Login Credentials

All users have the password: `password123`

- **Super Admin**: superadmin@lms.com
- **Tenant 1 Admin**: admin@acme-university.com
- **Tenant 2 Admin**: admin@tech-academy.com
- **Staff**: alice@acme-university.com
- **Tutor**: bob@acme-university.com
- **Student**: charlie@student.acme-university.com

## API Usage

### 1. Authentication
Use Laravel Sanctum for API authentication:
```bash
POST /api/v1/auth/login
{
  "email": "admin@acme-university.com",
  "password": "password123"
}
```

### 2. Tenant Context
Include tenant information in requests:
```bash
# Using Header
X-Tenant-ID: 1

# Or use subdomain routing
# acme-university.yourdomain.com
# tech-academy.yourdomain.com
```

### 3. Super Admin Access
Super admins can access all tenants without restrictions:
```bash
Authorization: Bearer {super_admin_token}
```

## Testing Multi-Tenancy

### 1. Test Tenant Isolation
```php
// In Tinker
$tenant1Users = User::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)->where('tenant_id', 1)->get();
$tenant2Users = User::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)->where('tenant_id', 2)->get();
```

### 2. Test Super Admin Access
```php
// Login as super admin
Auth::login(User::where('role', 'super_admin')->first());

// Should see all users
$allUsers = User::all();
```

### 3. Test Regular Admin Access
```php
// Login as tenant admin
Auth::login(User::where('email', 'admin@acme-university.com')->first());

// Should only see users from their tenant
$tenantUsers = User::all();
```

## Development Tips

### 1. Bypassing Tenant Scope
```php
// To see all records regardless of tenant
Model::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)->get();
```

### 2. Setting Tenant Context
```php
// In middleware or controller
request()->headers->set('X-Tenant-ID', $tenantId);
```

### 3. Check User Permissions
```php
// Check if user is super admin
if (auth()->user()->role === 'super_admin') {
    // Has access to all tenants
}

// Check if user is tenant admin
if (auth()->user()->role === 'admin') {
    // Has admin access to their tenant
}
```

## Frontend Integration

### 1. Send Tenant ID
```javascript
// In your API service
axios.defaults.headers.common['X-Tenant-ID'] = tenantId;

// Or in individual requests
axios.post('/api/v1/auth/login', data, {
  headers: {
    'X-Tenant-ID': tenantId
  }
});
```

### 2. Handle Multi-Tenant Routing
```javascript
// Detect tenant from subdomain
const getTenantFromSubdomain = () => {
  const subdomain = window.location.hostname.split('.')[0];
  return subdomain;
};

// Set tenant context
const tenantId = getTenantFromSubdomain();
```

## Security Considerations

1. **Tenant Isolation**: Always verify tenant_id in API endpoints
2. **Role-Based Access**: Implement proper role checking
3. **Data Leakage**: Test that users can't access other tenants' data
4. **Super Admin**: Restrict super admin access to authorized personnel only
5. **Header Validation**: Validate X-Tenant-ID header against user's tenant

## Next Steps

1. Set up subdomain routing
2. Implement tenant-specific themes
3. Add tenant-specific configurations
4. Set up tenant-specific file storage
5. Implement tenant-specific email templates
