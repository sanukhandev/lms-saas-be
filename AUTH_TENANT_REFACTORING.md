# LMS Authentication & Tenant System Refactoring

## Overview

This document outlines the refactoring of the Authentication and Tenant controllers to implement best practices including:

-   **DTOs (Data Transfer Objects)** for structured data handling
-   **API Response Trait** for consistent response formatting
-   **Service Layer** for business logic separation
-   **Enhanced Tenant Validation** for admin login security
-   **Comprehensive Error Handling** with proper logging

## Architecture Changes

### 1. Request Validation Layer

-   **Location**: `app/Http/Requests/Auth/` and `app/Http/Requests/Tenant/`
-   **Purpose**: Centralized validation rules and error messages
-   **Files Created**:
    -   `LoginRequest.php` - Login validation with tenant domain support
    -   `RegisterRequest.php` - User registration validation
    -   `ChangePasswordRequest.php` - Password change validation
    -   `UpdateTenantSettingsRequest.php` - Tenant settings validation

### 2. Data Transfer Objects (DTOs)

-   **Location**: `app/DTOs/Auth/` and `app/DTOs/Tenant/`
-   **Purpose**: Type-safe data structures for API communication
-   **Files Created**:
    -   `LoginDTO.php` - Login data structure
    -   `RegisterDTO.php` - Registration data structure
    -   `ChangePasswordDTO.php` - Password change data structure
    -   `AuthResponseDTO.php` - Authentication response structure
    -   `UpdateTenantSettingsDTO.php` - Tenant settings update structure

### 3. Service Layer

-   **Location**: `app/Services/Auth/` and `app/Services/Tenant/`
-   **Purpose**: Business logic separation and reusability
-   **Files Created**:
    -   `AuthService.php` - Authentication business logic
    -   `TenantService.php` - Tenant management business logic

### 4. Exception Handling

-   **Location**: `app/Exceptions/Auth/`
-   **Purpose**: Custom exceptions for better error handling
-   **Files Created**:
    -   `InvalidCredentialsException.php`
    -   `TenantValidationException.php`
    -   `UserNotFoundException.php`

## Key Features

### Enhanced Authentication Flow

#### 1. User Registration

```php
// DTO Structure
$registerDTO = new RegisterDTO(
    name: 'John Doe',
    email: 'john@example.com',
    password: 'password123',
    tenantId: 1,
    role: 'student'
);

// Service Call
$authResponse = $authService->register($registerDTO);
```

#### 2. Login with Tenant Validation

```php
// DTO Structure
$loginDTO = new LoginDTO(
    email: 'john@example.com',
    password: 'password123',
    tenantDomain: 'acme-university'
);

// Service Call with automatic tenant validation
$authResponse = $authService->login($loginDTO);
```

### Tenant Access Control

#### Admin/Staff Tenant Validation

-   **Super Admin**: Can access any tenant
-   **Admin/Staff**: Must belong to the requested tenant
-   **Tutor/Student**: Access their own tenant only

#### Business Logic

```php
private function validateTenantAccess(User $user, string $tenantDomain): void
{
    // Super admin can access any tenant
    if ($user->role === 'super_admin') {
        return;
    }

    // For admin/staff users, validate they belong to the requested tenant
    if (in_array($user->role, ['admin', 'staff'])) {
        $tenant = $this->tenantService->findByDomain($tenantDomain);

        if (!$tenant) {
            throw new TenantValidationException('Invalid tenant domain');
        }

        if ($user->tenant_id !== $tenant->id) {
            throw new TenantValidationException(
                'You are not authorized to access this tenant'
            );
        }
    }
}
```

### API Response Standardization

#### Success Response

```json
{
    "success": true,
    "message": "Login successful",
    "data": {
        "user": {
            "id": 1,
            "name": "John Doe",
            "email": "john@example.com",
            "role": "student",
            "tenant": {
                "id": 1,
                "name": "Acme University",
                "domain": "acme-university"
            }
        },
        "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
        "token_type": "Bearer"
    }
}
```

#### Error Response

```json
{
    "success": false,
    "message": "Invalid credentials provided",
    "errors": {
        "email": ["The provided credentials are invalid."]
    }
}
```

## Security Enhancements

### 1. Tenant Validation for Admin Login

-   Admin users must provide `tenant_domain` in login request
-   System validates admin belongs to the requested tenant
-   Prevents unauthorized cross-tenant access

### 2. Token Security

-   All existing tokens are revoked on new login
-   Secure token refresh mechanism
-   Proper token cleanup on logout

### 3. Comprehensive Logging

-   All authentication attempts are logged
-   Failed login attempts with context
-   Tenant access validation logs
-   Error tracking with full context

## API Endpoints

### Authentication Endpoints

#### POST /api/v1/auth/register

```json
{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123",
    "tenant_id": 1,
    "role": "student"
}
```

#### POST /api/v1/auth/login

```json
{
    "email": "admin@acme.com",
    "password": "password123",
    "tenant_domain": "acme-university"
}
```

#### POST /api/v1/auth/change-password

```json
{
    "current_password": "oldpassword",
    "new_password": "newpassword123",
    "new_password_confirmation": "newpassword123"
}
```

### Tenant Endpoints

#### GET /api/v1/tenants/domain/{domain}

-   Public endpoint for tenant information
-   Returns tenant configuration with theme settings

#### GET /api/v1/tenants/current

-   Protected endpoint for current user's tenant
-   Super admin gets all tenants

#### PUT /api/v1/tenants/{domain}/settings

-   Update tenant settings
-   Validates user access to tenant
-   Supports partial updates

## Error Handling

### Custom Exceptions

```php
// Invalid login credentials
throw new InvalidCredentialsException('Invalid credentials provided');

// Tenant validation failure
throw new TenantValidationException('You are not authorized to access this tenant');

// User not found
throw new UserNotFoundException('User not found');
```

### Logging Strategy

```php
// Success logging
Log::info('User logged in successfully', [
    'user_id' => $user->id,
    'email' => $user->email,
    'tenant_id' => $user->tenant_id,
    'role' => $user->role,
    'tenant_domain' => $dto->tenantDomain,
]);

// Error logging
Log::error('User login failed', [
    'email' => $dto->email,
    'tenant_domain' => $dto->tenantDomain,
    'error' => $e->getMessage(),
]);
```

## Migration Guide

### From Old System

1. **Controllers**: Updated to use DTOs and Service layer
2. **Validation**: Moved to Request classes
3. **Responses**: Standardized using ApiResponseTrait
4. **Business Logic**: Moved to Service classes
5. **Error Handling**: Enhanced with custom exceptions

### Dependencies

-   Ensure all service classes are registered in `AppServiceProvider`
-   Update middleware aliases if needed
-   Run composer dump-autoload after creating new classes

## Testing

### Unit Tests

-   Service layer methods
-   DTO validation and transformation
-   Exception handling

### Integration Tests

-   Full authentication flow
-   Tenant validation scenarios
-   API response formats

### Sample Test Script

```php
// Located at: test_new_auth_system.php
// Demonstrates: Registration, Login, Tenant validation
```

## Benefits

1. **Code Organization**: Clear separation of concerns
2. **Type Safety**: DTOs provide compile-time validation
3. **Reusability**: Service layer can be used across different controllers
4. **Maintainability**: Centralized business logic
5. **Security**: Enhanced tenant validation and access control
6. **Consistency**: Standardized API responses
7. **Debugging**: Comprehensive logging and error tracking
8. **Scalability**: Easy to extend with new features

## Future Enhancements

1. **Rate Limiting**: Add authentication rate limiting
2. **2FA Support**: Two-factor authentication
3. **Social Login**: OAuth integration
4. **API Versioning**: Version-specific DTOs
5. **Caching**: Tenant configuration caching
6. **Audit Trail**: Comprehensive audit logging
7. **Performance**: Query optimization and caching
8. **Security**: Advanced threat detection
