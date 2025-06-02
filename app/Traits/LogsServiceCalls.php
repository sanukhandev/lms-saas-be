<?php

namespace App\Traits;

use Illuminate\Support\Facades\Log;

trait LogsServiceCalls
{
    protected function logServiceCall(string $method, array $data = []): void
    {
        Log::channel('tenant_dynamic')->info("[" . class_basename($this) . "] $method", $data);
    }

    protected function logServiceErros(string $method, array $data = []): void
    {
        Log::channel('tenant_dynamic')->error("[" . class_basename($this) . "] $method", $data);
    }

}
