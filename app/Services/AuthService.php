<?php

namespace App\Services;

use App\Traits\LogsServiceCalls;

class AuthService
{
    use LogsServiceCalls;


    public function login(array $credentials): array {
        try {
            $this->logServiceCall('login', $credentials);
            if (!auth()->attempt($credentials)) {
                throw new \Exception('Unauthorized', 401);
            }

            $user = auth()->user();
            $token = $user->createToken('auth_token')->plainTextToken;
            $this->logServiceCall('login_success', [
                'user_id' => $user->id,
                'token' => $token
            ]);
            return [
                'user' => $user,
                'token' => $token
            ];
        } catch (\Exception $e) {
            $this->logServiceErros('login_error', [
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
                'credentials' => $credentials
            ]);
            throw $e; // rethrow to let the controller handle it
        }
    }
}
