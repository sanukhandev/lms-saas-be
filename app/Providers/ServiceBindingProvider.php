<?php

namespace App\Providers;

use App\Services\AuthService;
use Illuminate\Support\ServiceProvider;
use App\Support\LoggingServiceProxy;

class ServiceBindingProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // List all services you want to wrap with proxy logging
        $this->bindWithProxy([
            AuthService::class,
            // Add other services here
        ]);
    }

    /**
     * Bind services to proxy wrapper with logging.
     *
     * @param array $serviceClasses
     */
    protected function bindWithProxy(array $serviceClasses): void
    {
        foreach ($serviceClasses as $serviceClass) {
            $this->app->bind($serviceClass, function ($app) use ($serviceClass) {
                $serviceInstance = $app->make($serviceClass);
                return LoggingServiceProxy::wrap($serviceInstance);
            });
        }
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
