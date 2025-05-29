<?php

namespace App\Traits;
use Illuminate\Support\Facades\Log;
trait LogsControllerActions
{
    protected function logAction(string $action, array $context = []): void
    {
        Log::info("[" . class_basename($this) . "] $action", array_merge([
            'user_id' => auth()->id()
        ], $context));
    }
}
