<?php

namespace App\Providers;

use App\Services\Cache\CourseCache;
use App\Services\Cache\UserCache;
use App\Services\Cache\DashboardCache;
use App\Services\Cache\CacheManager;
use Illuminate\Support\ServiceProvider;

class CacheServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register cache services as singletons
        $this->app->singleton(CourseCache::class, function ($app) {
            return new CourseCache();
        });

        $this->app->singleton(UserCache::class, function ($app) {
            return new UserCache();
        });

        $this->app->singleton(DashboardCache::class, function ($app) {
            return new DashboardCache();
        });

        $this->app->singleton(CacheManager::class, function ($app) {
            return new CacheManager(
                $app->make(CourseCache::class),
                $app->make(UserCache::class),
                $app->make(DashboardCache::class)
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
