<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Enrollment\StoreEnrollmentRequest;
use App\Http\Requests\Enrollment\UpdateEnrollmentRequest;
use App\Services\Enrollment\EnrollmentService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EnrollmentController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private readonly EnrollmentService $enrollmentService
    ) {}

    /**
     * Display a listing of enrollments
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = [
                'course_id' => $request->get('course_id'),
                'user_id' => $request->get('user_id'),
                'status' => $request->get('status'),
                'search' => $request->get('search'),
                'per_page' => $request->get('per_page', 15)
            ];

            $result = $this->enrollmentService->getEnrollmentsList($filters);

            if (!$result['success']) {
                return $this->errorResponse($result['message'], [], 400);
            }

            return $this->successResponse($result['data']);

        } catch (\Exception $e) {
            Log::error('EnrollmentController@index failed', [
                'filters' => $filters ?? [],
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse('Failed to fetch enrollments');
        }
    }

    /**
     * Store a newly created enrollment
     */
    public function store(StoreEnrollmentRequest $request): JsonResponse
    {
        try {
            $result = $this->enrollmentService->createEnrollment($request->validated());

            if (!$result['success']) {
                return $this->errorResponse($result['message'], [], 400);
            }

            return $this->successResponse($result['data'], $result['message'], 201);

        } catch (\Exception $e) {
            Log::error('EnrollmentController@store failed', [
                'data' => $request->validated(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse('Failed to create enrollment');
        }
    }

    /**
     * Display the specified enrollment
     */
    public function show(int $enrollmentId): JsonResponse
    {
        try {
            $result = $this->enrollmentService->getEnrollmentById($enrollmentId);

            if (!$result['success']) {
                return $this->errorResponse($result['message'], [], 404);
            }

            return $this->successResponse($result['data']);

        } catch (\Exception $e) {
            Log::error('EnrollmentController@show failed', [
                'enrollment_id' => $enrollmentId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse('Failed to fetch enrollment');
        }
    }

    /**
     * Update the specified enrollment
     */
    public function update(UpdateEnrollmentRequest $request, int $enrollmentId): JsonResponse
    {
        try {
            $result = $this->enrollmentService->updateEnrollment($enrollmentId, $request->validated());

            if (!$result['success']) {
                return $this->errorResponse($result['message'], [], 400);
            }

            return $this->successResponse($result['data'], $result['message']);

        } catch (\Exception $e) {
            Log::error('EnrollmentController@update failed', [
                'enrollment_id' => $enrollmentId,
                'data' => $request->validated(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse('Failed to update enrollment');
        }
    }

    /**
     * Remove the specified enrollment
     */
    public function destroy(int $enrollmentId): JsonResponse
    {
        try {
            $result = $this->enrollmentService->deleteEnrollment($enrollmentId);

            if (!$result['success']) {
                return $this->errorResponse($result['message'], [], 404);
            }

            return $this->successResponse([], $result['message']);

        } catch (\Exception $e) {
            Log::error('EnrollmentController@destroy failed', [
                'enrollment_id' => $enrollmentId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse('Failed to delete enrollment');
        }
    }

    /**
     * Get enrollment statistics
     */
    public function stats(): JsonResponse
    {
        try {
            $result = $this->enrollmentService->getEnrollmentStats();

            if (!$result['success']) {
                return $this->errorResponse($result['message'], [], 400);
            }

            return $this->successResponse($result['data']);

        } catch (\Exception $e) {
            Log::error('EnrollmentController@stats failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse('Failed to fetch enrollment statistics');
        }
    }
}
