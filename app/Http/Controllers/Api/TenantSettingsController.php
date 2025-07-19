<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TenantSettings\UpdateGeneralSettingsRequest;
use App\Http\Requests\TenantSettings\UpdateBrandingSettingsRequest;
use App\Http\Requests\TenantSettings\UpdateFeaturesSettingsRequest;
use App\Http\Requests\TenantSettings\UpdateSecuritySettingsRequest;
use App\Http\Requests\TenantSettings\UpdateThemeSettingsRequest;
use App\Services\TenantSettings\TenantSettingsService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class TenantSettingsController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private readonly TenantSettingsService $tenantSettingsService
    ) {}

    /**
     * Get tenant general settings
     */
    public function getGeneralSettings(): JsonResponse
    {
        try {
            $result = $this->tenantSettingsService->getGeneralSettings();

            if (!$result['success']) {
                return $this->errorResponse($result['message'], [], 404);
            }

            return $this->successResponse($result['data']);

        } catch (\Exception $e) {
            Log::error('TenantSettingsController@getGeneralSettings failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse('Failed to fetch general settings');
        }
    }

    /**
     * Update tenant general settings
     */
    public function updateGeneralSettings(UpdateGeneralSettingsRequest $request): JsonResponse
    {
        try {
            $result = $this->tenantSettingsService->updateGeneralSettings($request->validated());

            if (!$result['success']) {
                return $this->errorResponse($result['message'], [], 400);
            }

            return $this->successResponse($result['data'], 'General settings updated successfully');

        } catch (\Exception $e) {
            Log::error('TenantSettingsController@updateGeneralSettings failed', [
                'data' => $request->validated(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse('Failed to update general settings');
        }
    }

    /**
     * Get tenant branding settings
     */
    public function getBrandingSettings(): JsonResponse
    {
        try {
            $result = $this->tenantSettingsService->getBrandingSettings();

            if (!$result['success']) {
                return $this->errorResponse($result['message'], [], 404);
            }

            return $this->successResponse($result['data']);

        } catch (\Exception $e) {
            Log::error('TenantSettingsController@getBrandingSettings failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse('Failed to fetch branding settings');
        }
    }

    /**
     * Update tenant branding settings
     */
    public function updateBrandingSettings(UpdateBrandingSettingsRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            
            // Add file uploads to data if present
            if ($request->hasFile('logo_file')) {
                $data['logo_file'] = $request->file('logo_file');
            }
            if ($request->hasFile('favicon_file')) {
                $data['favicon_file'] = $request->file('favicon_file');
            }

            $result = $this->tenantSettingsService->updateBrandingSettings($data);

            if (!$result['success']) {
                return $this->errorResponse($result['message'], [], 400);
            }

            return $this->successResponse($result['data'], 'Branding settings updated successfully');

        } catch (\Exception $e) {
            Log::error('TenantSettingsController@updateBrandingSettings failed', [
                'data' => array_diff_key($request->validated(), ['logo_file' => '', 'favicon_file' => '']),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse('Failed to update branding settings');
        }
    }

    /**
     * Get tenant features settings
     */
    public function getFeaturesSettings(): JsonResponse
    {
        try {
            $result = $this->tenantSettingsService->getFeaturesSettings();

            if (!$result['success']) {
                return $this->errorResponse($result['message'], [], 404);
            }

            return $this->successResponse($result['data']);

        } catch (\Exception $e) {
            Log::error('TenantSettingsController@getFeaturesSettings failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse('Failed to fetch features settings');
        }
    }

    /**
     * Update tenant features settings
     */
    public function updateFeaturesSettings(UpdateFeaturesSettingsRequest $request): JsonResponse
    {
        try {
            $result = $this->tenantSettingsService->updateFeaturesSettings($request->validated());

            if (!$result['success']) {
                return $this->errorResponse($result['message'], [], 400);
            }

            return $this->successResponse($result['data'], 'Features settings updated successfully');

        } catch (\Exception $e) {
            Log::error('TenantSettingsController@updateFeaturesSettings failed', [
                'data' => $request->validated(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse('Failed to update features settings');
        }
    }

    /**
     * Get tenant security settings
     */
    public function getSecuritySettings(): JsonResponse
    {
        try {
            $result = $this->tenantSettingsService->getSecuritySettings();

            if (!$result['success']) {
                return $this->errorResponse($result['message'], [], 404);
            }

            return $this->successResponse($result['data']);

        } catch (\Exception $e) {
            Log::error('TenantSettingsController@getSecuritySettings failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse('Failed to fetch security settings');
        }
    }

    /**
     * Update tenant security settings
     */
    public function updateSecuritySettings(UpdateSecuritySettingsRequest $request): JsonResponse
    {
        try {
            $result = $this->tenantSettingsService->updateSecuritySettings($request->validated());

            if (!$result['success']) {
                return $this->errorResponse($result['message'], [], 400);
            }

            return $this->successResponse($result['data'], 'Security settings updated successfully');

        } catch (\Exception $e) {
            Log::error('TenantSettingsController@updateSecuritySettings failed', [
                'data' => $request->validated(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse('Failed to update security settings');
        }
    }

    /**
     * Get tenant theme settings
     */
    public function getThemeSettings(): JsonResponse
    {
        try {
            $result = $this->tenantSettingsService->getThemeSettings();

            if (!$result['success']) {
                return $this->errorResponse($result['message'], [], 404);
            }

            return $this->successResponse($result['data']);

        } catch (\Exception $e) {
            Log::error('TenantSettingsController@getThemeSettings failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse('Failed to fetch theme settings');
        }
    }

    /**
     * Update tenant theme settings
     */
    public function updateThemeSettings(UpdateThemeSettingsRequest $request): JsonResponse
    {
        try {
            $result = $this->tenantSettingsService->updateThemeSettings($request->validated());

            if (!$result['success']) {
                return $this->errorResponse($result['message'], [], 400);
            }

            return $this->successResponse($result['data'], 'Theme settings updated successfully');

        } catch (\Exception $e) {
            Log::error('TenantSettingsController@updateThemeSettings failed', [
                'data' => $request->validated(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse('Failed to update theme settings');
        }
    }

    /**
     * Get available color palettes
     */
    public function getColorPalettes(): JsonResponse
    {
        try {
            $result = $this->tenantSettingsService->getColorPalettes();

            return $this->successResponse($result['data']);

        } catch (\Exception $e) {
            Log::error('TenantSettingsController@getColorPalettes failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse('Failed to fetch color palettes');
        }
    }

    /**
     * Get preset themes
     */
    public function getPresetThemes(): JsonResponse
    {
        try {
            $result = $this->tenantSettingsService->getPresetThemes();

            return $this->successResponse($result['data']);

        } catch (\Exception $e) {
            Log::error('TenantSettingsController@getPresetThemes failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse('Failed to fetch preset themes');
        }
    }
}
