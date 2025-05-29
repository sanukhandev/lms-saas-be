<?php

namespace App\Traits;

use Illuminate\Support\Facades\Log;

trait LogsServiceCalls
{
    protected function logServiceCall(string $method, array $data = []): void
    {
        Log::info("[" . class_basename($this) . "] $method", $data);
    }
}
