<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Pagination\Paginator;

trait ApiResponseTrait
{
    protected function successResponse($data = [], string $message = 'Success', int $code = 200, array $meta = []): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => $data,
            'meta' => $meta
        ], $code);
    }

    protected function successPaginated(Paginator $paginator, string $message = 'Success', int $code = 200): JsonResponse
    {
        return $this->successResponse(
            $paginator->items(),
            $message,
            $code,
            [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage()
            ]
        );
    }

    protected function errorResponse(string $message = 'Something went wrong', int $code = 500, $errors = []): JsonResponse
    {
        Log::error("[API ERROR] $message", [
            'code' => $code,
            'errors' => $errors,
            'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5)
        ]);

        return response()->json([
            'status' => 'error',
            'message' => $message,
            'errors' => $errors
        ], $code);
    }

    protected function validationErrorResponse($errors, string $message = 'Validation failed', int $code = 422): JsonResponse
    {
        return $this->errorResponse($message, $code, $errors);
    }

    protected function notFoundResponse(string $message = 'Resource not found'): JsonResponse
    {
        return $this->errorResponse($message, 404);
    }

    protected function unauthorizedResponse(string $message = 'Unauthorized'): JsonResponse
    {
        return $this->errorResponse($message, 401);
    }
}
