<?php

namespace App\Http\Controllers\Api;

use App\DTOs\Auth\AuthResponseDTO;
use App\DTOs\Auth\ChangePasswordDTO;
use App\DTOs\Auth\LoginDTO;
use App\DTOs\Auth\RegisterDTO;
use App\Exceptions\Auth\InvalidCredentialsException;
use App\Exceptions\Auth\TenantValidationException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Services\Auth\AuthService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private readonly AuthService $authService
    ) {}

    /**
     * Register a new user
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            $dto = RegisterDTO::fromRequest($request->validated());
            $authResponse = $this->authService->register($dto);

            return $this->successResponse(
                data: $authResponse->toArray(),
                message: 'User registered successfully',
                code: 201
            );
        } catch (TenantValidationException $e) {
            return $this->validationErrorResponse(
                errors: ['tenant_id' => [$e->getMessage()]],
                message: 'Tenant validation failed'
            );
        } catch (\Exception $e) {
            Log::error('Registration failed', [
                'error' => $e->getMessage(),
                'request_data' => $request->except(['password', 'password_confirmation'])
            ]);

            return $this->errorResponse(
                message: 'Registration failed',
                code: 500
            );
        }
    }

    /**
     * Login user
     */
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $dto = LoginDTO::fromRequest($request->validated());
            $authResponse = $this->authService->login($dto);

            return $this->successResponse(
                data: $authResponse->toArray(),
                message: 'Login successful'
            );
        } catch (InvalidCredentialsException $e) {
            return $this->unauthorizedResponse($e->getMessage());
        } catch (TenantValidationException $e) {
            return $this->forbiddenResponse($e->getMessage());
        } catch (\Exception $e) {
            Log::error('Login failed', [
                'error' => $e->getMessage(),
                'email' => $request->email
            ]);

            return $this->errorResponse(
                message: 'Login failed',
                code: 500
            );
        }
    }

    /**
     * Logout user
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            $this->authService->logout($request->user());

            return $this->successResponse(
                message: 'Logged out successfully'
            );
        } catch (\Exception $e) {
            Log::error('Logout failed', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id
            ]);

            return $this->errorResponse(
                message: 'Logout failed',
                code: 500
            );
        }
    }

    /**
     * Get authenticated user
     */
    public function user(Request $request): JsonResponse
    {
        return $this->successResponse(
            data: ['user' => $request->user()->load('tenant')],
            message: 'User retrieved successfully'
        );
    }

    /**
     * Refresh token
     */
    public function refresh(Request $request): JsonResponse
    {
        try {
            $authResponse = $this->authService->refresh($request->user());

            return $this->successResponse(
                data: $authResponse->toArray(),
                message: 'Token refreshed successfully'
            );
        } catch (\Exception $e) {
            Log::error('Token refresh failed', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id
            ]);

            return $this->errorResponse(
                message: 'Token refresh failed',
                code: 500
            );
        }
    }

    /**
     * Change password
     */
    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        try {
            $dto = ChangePasswordDTO::fromRequest($request->validated());
            $this->authService->changePassword($request->user(), $dto);

            return $this->successResponse(
                message: 'Password changed successfully'
            );
        } catch (InvalidCredentialsException $e) {
            return $this->validationErrorResponse(
                errors: ['current_password' => [$e->getMessage()]],
                message: 'Current password is incorrect'
            );
        } catch (\Exception $e) {
            Log::error('Password change failed', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id
            ]);

            return $this->errorResponse(
                message: 'Password change failed',
                code: 500
            );
        }
    }
}
