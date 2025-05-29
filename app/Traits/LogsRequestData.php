<?php

namespace App\Traits;

use Illuminate\Support\Facades\Log;

trait LogsRequestData
{
    protected function logRequest(string $note = null): void
    {
        Log::info("[" . class_basename($this) . "] validated", [
            'note' => $note,
            'data' => $this->validated(),
            'user_id' => auth()->id()
        ]);
    }
}
