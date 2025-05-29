<?php

namespace App\Traits;

use Illuminate\Support\Facades\Log;

trait LogsModelEvents
{
    public static function bootLogsModelEvents(): void
    {
        static::created(function ($model) {
            Log::info('Created: ' . class_basename($model), [
                'id' => $model->id,
                'data' => $model->toArray(),
                'user_id' => auth()->id()
            ]);
        });

        static::updated(function ($model) {
            Log::info('Updated: ' . class_basename($model), [
                'id' => $model->id,
                'changes' => $model->getChanges(),
                'user_id' => auth()->id()
            ]);
        });

        static::deleted(function ($model) {
            Log::info('Deleted: ' . class_basename($model), [
                'id' => $model->id,
                'user_id' => auth()->id()
            ]);
        });
    }
}
