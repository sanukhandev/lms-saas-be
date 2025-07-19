<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Services\User\UserService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private readonly UserService $userService
    ) {}

    /**
     * Display a listing of users
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = [
                'search' => $request->get('search'),
                'role' => $request->get('role'),
                'status' => $request->get('status'),
                'per_page' => $request->get('per_page', 15)
            ];

            $result = $this->userService->getUsersList($filters);

            if (!$result['success']) {
                return $this->errorResponse($result['message'], [], 400);
            }

            return $this->successResponse($result['data']);

        } catch (\Exception $e) {
            Log::error('UserController@index failed', [
                'filters' => $filters ?? [],
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse('Failed to fetch users');
        }
    }

    /**
     * Store a newly created user
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        try {
            $result = $this->userService->createUser($request->validated());

            if (!$result['success']) {
                return $this->errorResponse($result['message'], [], 400);
            }

            return $this->successResponse($result['data'], $result['message'], 201);

        } catch (\Exception $e) {
            Log::error('UserController@store failed', [
                'data' => $request->validated(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse('Failed to create user');
        }
    }

    /**
     * Display the specified user
     */
    public function show(int $userId): JsonResponse
    {
        try {
            $result = $this->userService->getUserById($userId);

            if (!$result['success']) {
                return $this->errorResponse($result['message'], [], 404);
            }

            return $this->successResponse($result['data']);

        } catch (\Exception $e) {
            Log::error('UserController@show failed', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse('Failed to fetch user');
        }
    }

    /**
     * Update the specified user
     */
    public function update(UpdateUserRequest $request, int $userId): JsonResponse
    {
        try {
            $result = $this->userService->updateUser($userId, $request->validated());

            if (!$result['success']) {
                return $this->errorResponse($result['message'], [], 400);
            }

            return $this->successResponse($result['data'], $result['message']);

        } catch (\Exception $e) {
            Log::error('UserController@update failed', [
                'user_id' => $userId,
                'data' => $request->validated(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse('Failed to update user');
        }
    }

    /**
     * Remove the specified user
     */
    public function destroy(int $userId): JsonResponse
    {
        try {
            $result = $this->userService->deleteUser($userId);

            if (!$result['success']) {
                return $this->errorResponse($result['message'], [], 404);
            }

            return $this->successResponse([], $result['message']);

        } catch (\Exception $e) {
            Log::error('UserController@destroy failed', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse('Failed to delete user');
        }
    }

    /**
     * Get user statistics
     */
    public function statistics(): JsonResponse
    {
        try {
            $result = $this->userService->getUserStats();

            if (!$result['success']) {
                return $this->errorResponse($result['message'], [], 400);
            }

            return $this->successResponse($result['data']);

        } catch (\Exception $e) {
            Log::error('UserController@statistics failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse('Failed to fetch user statistics');
        }
    }

    /**
     * Get users by role
     */
    public function getByRole(Request $request, string $role): JsonResponse
    {
        try {
            $filters = [
                'role' => $role,
                'search' => $request->get('search'),
                'per_page' => $request->get('per_page', 15)
            ];

            $result = $this->userService->getUsersList($filters);

            if (!$result['success']) {
                return $this->errorResponse($result['message'], [], 400);
            }

            return $this->successResponse($result['data']);

        } catch (\Exception $e) {
            Log::error('UserController@getByRole failed', [
                'role' => $role,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse('Failed to fetch users by role');
        }
    }

    /**
     * Bulk import users
     */
    public function bulkImport(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'users' => 'required|array|min:1',
                'users.*.name' => 'required|string|max:255',
                'users.*.email' => 'required|email|unique:users,email',
                'users.*.role' => 'required|in:admin,instructor,student'
            ]);

            $result = $this->userService->bulkCreateUsers($request->input('users'));

            if (!$result['success']) {
                return $this->errorResponse($result['message'], [], 400);
            }

            return $this->successResponse($result['data'], $result['message'], 201);

        } catch (\Exception $e) {
            Log::error('UserController@bulkImport failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse('Failed to import users');
        }
    }
}
