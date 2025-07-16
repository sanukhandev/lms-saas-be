<?php

namespace App\Services\Auth;

use App\DTOs\Auth\AuthResponseDTO;
use App\DTOs\Auth\ChangePasswordDTO;
use App\DTOs\Auth\LoginDTO;
use App\DTOs\Auth\RegisterDTO;
use App\Exceptions\Auth\InvalidCredentialsException;
use App\Exceptions\Auth\TenantValidationException;
use App\Exceptions\Auth\UserNotFoundException;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Tenant\TenantService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AuthService
{
    public function __construct(
        private readonly TenantService $tenantService
    ) {}

    /**
     * Register a new user
     */
    public function register(RegisterDTO $dto): AuthResponseDTO
    {
        try {
            // If tenant_id is provided, validate it exists
            if ($dto->tenantId) {
                $tenant = $this->tenantService->findById($dto->tenantId);
                if (!$tenant) {
                    throw new TenantValidationException('Invalid tenant ID provided');
                }
            }

            $user = User::create([
                'name' => $dto->name,
                'email' => $dto->email,
                'password' => Hash::make($dto->password),
                'tenant_id' => $dto->tenantId,
                'role' => $dto->role,
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;

            Log::info('User registered successfully', [
                'user_id' => $user->id,
                'email' => $user->email,
                'tenant_id' => $user->tenant_id,
                'role' => $user->role,
            ]);

            return new AuthResponseDTO(
                user: $user,
                token: $token,
                message: 'User registered successfully'
            );
        } catch (\Exception $e) {
            Log::error('User registration failed', [
                'email' => $dto->email,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Login user with tenant validation
     */
    public function login(LoginDTO $dto): AuthResponseDTO
    {
        try {
            $user = User::where('email', $dto->email)->first();

            if (!$user || !Hash::check($dto->password, $user->password)) {
                throw new InvalidCredentialsException('Invalid credentials provided');
            }

            // Validate tenant access for admin users
            if ($dto->tenantDomain && in_array($user->role, ['admin', 'staff'])) {
                $this->validateTenantAccess($user, $dto->tenantDomain);
            }

            // Revoke all existing tokens for security
            $user->tokens()->delete();

            $token = $user->createToken('auth_token')->plainTextToken;

            Log::info('User logged in successfully', [
                'user_id' => $user->id,
                'email' => $user->email,
                'tenant_id' => $user->tenant_id,
                'role' => $user->role,
                'tenant_domain' => $dto->tenantDomain,
            ]);

            return new AuthResponseDTO(
                user: $user,
                token: $token,
                message: 'Login successful'
            );
        } catch (\Exception $e) {
            Log::error('User login failed', [
                'email' => $dto->email,
                'tenant_domain' => $dto->tenantDomain,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Refresh user token
     */
    public function refresh(User $user): AuthResponseDTO
    {
        try {
            // Revoke current token
            $user->tokens()->where('id', $user->currentAccessToken()->id)->delete();

            $token = $user->createToken('auth_token')->plainTextToken;

            Log::info('Token refreshed successfully', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);

            return new AuthResponseDTO(
                user: $user,
                token: $token,
                message: 'Token refreshed successfully'
            );
        } catch (\Exception $e) {
            Log::error('Token refresh failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Change user password
     */
    public function changePassword(User $user, ChangePasswordDTO $dto): bool
    {
        try {
            if (!Hash::check($dto->currentPassword, $user->password)) {
                throw new InvalidCredentialsException('Current password is incorrect');
            }

            $user->update([
                'password' => Hash::make($dto->newPassword),
            ]);

            Log::info('Password changed successfully', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Password change failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Logout user
     */
    public function logout(User $user): bool
    {
        try {
            $user->tokens()->where('id', $user->currentAccessToken()->id)->delete();

            Log::info('User logged out successfully', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('User logout failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Validate tenant access for admin users
     */
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
}
