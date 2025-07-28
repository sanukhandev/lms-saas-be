<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Session\{UpdateSessionRequest, MarkAttendanceRequest};
use App\Services\Session\SessionService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SessionController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private readonly SessionService $sessionService
    ) {}

    /**
     * Get all sessions for a course
     */
    public function index(string $courseId): JsonResponse
    {
        try {
            $tenantId = $this->getTenantId();
            $sessions = $this->sessionService->getCourseSessions($courseId, $tenantId);

            return $this->successResponse(
                $sessions->toArray(),
                'Course sessions retrieved successfully'
            );
        } catch (\Exception $e) {
            Log::error('Error retrieving course sessions', [
                'error' => $e->getMessage(),
                'course_id' => $courseId,
                'tenant_id' => $this->getTenantId(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                message: 'Failed to retrieve course sessions',
                code: 500
            );
        }
    }

    /**
     * Get specific session details
     */
    public function show(string $courseId, string $sessionId): JsonResponse
    {
        try {
            $tenantId = $this->getTenantId();
            $session = $this->sessionService->getSessionDetails($sessionId, $tenantId);

            if (!$session) {
                return $this->errorResponse(
                    message: 'Session not found',
                    code: 404
                );
            }

            return $this->successResponse(
                $session->toArray(),
                'Session details retrieved successfully'
            );
        } catch (\Exception $e) {
            Log::error('Error retrieving session details', [
                'error' => $e->getMessage(),
                'course_id' => $courseId,
                'session_id' => $sessionId,
                'tenant_id' => $this->getTenantId(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                message: 'Failed to retrieve session details',
                code: 500
            );
        }
    }

    /**
     * Update session
     */
    public function update(UpdateSessionRequest $request, string $courseId, string $sessionId): JsonResponse
    {
        try {
            $tenantId = $this->getTenantId();
            $data = $request->validated();

            $session = $this->sessionService->updateSession($sessionId, $data, $tenantId);

            if (!$session) {
                return $this->errorResponse(
                    message: 'Session not found',
                    code: 404
                );
            }

            return $this->successResponse(
                $session->toArray(),
                'Session updated successfully'
            );
        } catch (\Exception $e) {
            Log::error('Error updating session', [
                'error' => $e->getMessage(),
                'course_id' => $courseId,
                'session_id' => $sessionId,
                'tenant_id' => $this->getTenantId(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                message: 'Failed to update session',
                code: 500
            );
        }
    }

    /**
     * Start a session
     */
    public function startSession(string $courseId, string $sessionId): JsonResponse
    {
        try {
            $tenantId = $this->getTenantId();
            $session = $this->sessionService->startSession($sessionId, $tenantId);

            if (!$session) {
                return $this->errorResponse(
                    message: 'Session not found',
                    code: 404
                );
            }

            return $this->successResponse(
                $session->toArray(),
                'Session started successfully'
            );
        } catch (\Exception $e) {
            Log::error('Error starting session', [
                'error' => $e->getMessage(),
                'course_id' => $courseId,
                'session_id' => $sessionId,
                'tenant_id' => $this->getTenantId(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                message: 'Failed to start session',
                code: 500
            );
        }
    }

    /**
     * End a session
     */
    public function endSession(string $courseId, string $sessionId): JsonResponse
    {
        try {
            $tenantId = $this->getTenantId();
            $session = $this->sessionService->endSession($sessionId, $tenantId);

            if (!$session) {
                return $this->errorResponse(
                    message: 'Session not found',
                    code: 404
                );
            }

            return $this->successResponse(
                $session->toArray(),
                'Session ended successfully'
            );
        } catch (\Exception $e) {
            Log::error('Error ending session', [
                'error' => $e->getMessage(),
                'course_id' => $courseId,
                'session_id' => $sessionId,
                'tenant_id' => $this->getTenantId(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                message: 'Failed to end session',
                code: 500
            );
        }
    }

    /**
     * Mark attendance for session
     */
    public function markAttendance(MarkAttendanceRequest $request, string $courseId, string $sessionId): JsonResponse
    {
        try {
            $tenantId = $this->getTenantId();
            $data = $request->validated();

            $result = $this->sessionService->markAttendance($sessionId, $data, $tenantId);

            if (!$result) {
                return $this->errorResponse(
                    message: 'Session not found or attendance already marked',
                    code: 404
                );
            }

            return $this->successResponse(
                $result,
                'Attendance marked successfully'
            );
        } catch (\Exception $e) {
            Log::error('Error marking attendance', [
                'error' => $e->getMessage(),
                'course_id' => $courseId,
                'session_id' => $sessionId,
                'tenant_id' => $this->getTenantId(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                message: 'Failed to mark attendance',
                code: 500
            );
        }
    }

    /**
     * Get tenant ID from authenticated user
     */
    private function getTenantId(): string
    {
        return Auth::user()->tenant_id;
    }
}
