<?php

namespace App\Traits;

use Illuminate\Support\Facades\Log;

trait LogsRepositoryQueries
{
    protected function logQuery(string $method, array $params = []): void
    {
        Log::debug("[" . class_basename($this) . "] $method", $params);
    }
}
