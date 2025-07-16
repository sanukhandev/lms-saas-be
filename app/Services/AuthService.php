<?php

namespace App\Services;

use App\DTOs\LoginDTO;
use App\DTOs\RegisterDTO;
use App\DTOs\ChangePasswordDTO;
use App\Models\User;
use App\Models\Tenant;
use App\Exceptions\TenantValidationException;
use App\Traits\LogsServiceCalls;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AuthService
{
    use LogsServiceCalls;

    public function login(LoginDTO $loginDTO): array
    {
        try {
            Log::info('Authentication attempt', [
                'email' => $loginDTO->email,
                'tenant_slug' => $loginDTO->tenantSlug,
                'ip' => request()->ip(),
                'user_agent' => request()->header('User-Agent')
            ]);

            // Find the tenant
            $tenant = Tenant::where('slug', $loginDTO->tenantSlug)
                ->where('status', 'active')
                ->first();

            if (!$tenant) {
                throw new TenantValidationException('Tenant not found or inactive');
            }

            // Attempt authentication (without tenant_id first)
            if (!Auth::attempt([
                'email' => $loginDTO->email,
                'password' => $loginDTO->password,
            ])) {
                throw new \Exception('Invalid credentials');
            }

            $user = Auth::user();

            // Validate tenant access
            if (!$this->validateTenantAccess($user, $tenant)) {
                Auth::logout(); // Log out the user since they don't have access
                throw new TenantValidationException('User does not have access to this tenant');
            }

            // Additional validation for admin users
            if ($user->role === 'admin' && $tenant->status !== 'active') {
                throw new TenantValidationException('Tenant not found or inactive');
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            Log::info('User authenticated successfully', [
                'user_id' => $user->id,
                'tenant_id' => $tenant->id,
                'role' => $user->role
            ]);

            return [
                'user' => $user,
                'token' => $token,
                'tenant' => $tenant
            ];
        } catch (\Exception $e) {
            $this->logServiceErros('login_error', [
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
                'credentials' => $loginDTO->toArray()
            ]);
            throw $e;
        }
    }

    public function register(RegisterDTO $registerDTO): array
    {
        try {
            // Find the tenant
            $tenant = Tenant::where('slug', $registerDTO->tenantSlug)
                ->where('status', 'active')
                ->first();

            if (!$tenant) {
                throw new TenantValidationException('Tenant not found or inactive');
            }

            // Check if user already exists in this tenant
            $existingUser = User::where('email', $registerDTO->email)
                ->where('tenant_id', $tenant->id)
                ->first();

            if ($existingUser) {
                throw new \Exception('User already exists in this tenant');
            }

            // Create the user
            $user = User::create([
                'name' => $registerDTO->name,
                'email' => $registerDTO->email,
                'password' => Hash::make($registerDTO->password),
                'tenant_id' => $tenant->id,
                'role' => $registerDTO->role,
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;

            return [
                'user' => $user,
                'token' => $token,
                'tenant' => $tenant
            ];
        } catch (\Exception $e) {
            $this->logServiceErros('register_error', [
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
                'data' => $registerDTO->toArray()
            ]);
            throw $e;
        }
    }

    public function changePassword(ChangePasswordDTO $changePasswordDTO): bool
    {
        try {
            $user = Auth::user();

            if (!$user) {
                throw new \Exception('User not authenticated');
            }

            // Verify current password
            if (!Hash::check($changePasswordDTO->currentPassword, $user->password)) {
                throw new \Exception('Current password is incorrect');
            }

            // Check if new password matches confirmation
            if ($changePasswordDTO->newPassword !== $changePasswordDTO->confirmPassword) {
                throw new \Exception('New password confirmation does not match');
            }

            // Update password
            $user->update([
                'password' => Hash::make($changePasswordDTO->newPassword)
            ]);

            Log::info('Password changed successfully', [
                'user_id' => $user->id,
            ]);

            return true;
        } catch (\Exception $e) {
            $this->logServiceErros('change_password_error', [
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
                'user_id' => Auth::id()
            ]);
            throw $e;
        }
    }

    public function logout(): bool
    {
        try {
            $user = Auth::user();

            if ($user) {
                // Revoke all tokens
                $user->tokens()->delete();
                Auth::logout();

                Log::info('User logged out successfully', [
                    'user_id' => $user->id,
                ]);
            }

            return true;
        } catch (\Exception $e) {
            $this->logServiceErros('logout_error', [
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
                'user_id' => Auth::id()
            ]);
            throw $e;
        }
    }

    public function validateTenantAccess(User $user, Tenant $tenant): bool
    {
        return $user->tenant_id === $tenant->id;
    }
}
