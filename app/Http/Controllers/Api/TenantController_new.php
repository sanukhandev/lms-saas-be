<?php

namespace App\Http\Controllers\Api;

use App\DTOs\Tenant\UpdateTenantSettingsDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\UpdateTenantSettingsRequest;
use App\Services\Tenant\TenantService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TenantController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private readonly TenantService $tenantService
    ) {}

    /**
     * Get tenant by domain
     */
    public function getByDomain(Request $request, string $domain): JsonResponse
    {
        try {
            $tenantData = $this->tenantService->getTenantWithDefaultSettings($domain);

            if (empty($tenantData)) {
                return $this->notFoundResponse(
                    message: "No tenant found with domain: {$domain}"
                );
            }

            return $this->successResponse(
                data: ['tenant' => $tenantData],
                message: 'Tenant retrieved successfully'
            );
        } catch (\Exception $e) {
            Log::error('Failed to retrieve tenant by domain', [
                'domain' => $domain,
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse(
                message: 'Failed to retrieve tenant',
                code: 500
            );
        }
    }

    /**
     * Get all tenants (super admin only)
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if ($user->role !== 'super_admin') {
                return $this->forbiddenResponse(
                    message: 'Only super administrators can view all tenants'
                );
            }

            $tenants = $this->tenantService->getAllTenants();

            return $this->successResponse(
                data: ['tenants' => $tenants],
                message: 'Tenants retrieved successfully'
            );
        } catch (\Exception $e) {
            Log::error('Failed to retrieve tenants', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse(
                message: 'Failed to retrieve tenants',
                code: 500
            );
        }
    }

    /**
     * Get current tenant for authenticated user
     */
    public function current(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if ($user->role === 'super_admin') {
                // Super admin can access all tenants
                $tenants = $this->tenantService->getAllTenants();
                return $this->successResponse(
                    data: [
                        'message' => 'Super admin access - all tenants available',
                        'tenants' => $tenants,
                        'current_tenant' => null
                    ],
                    message: 'Super admin tenant access'
                );
            }

            $tenant = $this->tenantService->getCurrentTenant($user);

            if (!$tenant) {
                return $this->notFoundResponse(
                    message: 'No tenant found for current user'
                );
            }

            return $this->successResponse(
                data: ['tenant' => $tenant],
                message: 'Current tenant retrieved successfully'
            );
        } catch (\Exception $e) {
            Log::error('Failed to retrieve current tenant', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse(
                message: 'Failed to retrieve current tenant',
                code: 500
            );
        }
    }

    /**
     * Update tenant settings
     */
    public function updateSettings(
        UpdateTenantSettingsRequest $request,
        string $domain
    ): JsonResponse {
        try {
            $user = $request->user();
            $tenant = $this->tenantService->findByDomain($domain);

            if (!$tenant) {
                return $this->notFoundResponse(
                    message: "No tenant found with domain: {$domain}"
                );
            }

            // Validate user has permission to update this tenant
            if (!$this->tenantService->validateUserAccess($user, $tenant)) {
                return $this->forbiddenResponse(
                    message: 'You are not authorized to update this tenant'
                );
            }

            $dto = UpdateTenantSettingsDTO::fromRequest($request->validated());
            $updatedTenant = $this->tenantService->updateSettings($tenant, $dto);

            return $this->successResponse(
                data: ['tenant' => $updatedTenant],
                message: 'Tenant settings updated successfully'
            );
        } catch (\Exception $e) {
            Log::error('Failed to update tenant settings', [
                'domain' => $domain,
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse(
                message: 'Failed to update tenant settings',
                code: 500
            );
        }
    }
}
