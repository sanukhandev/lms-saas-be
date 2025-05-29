<?php
namespace App\Utils;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Storage;

class TenantLogger
{
    public function __invoke(array $config): \Monolog\Logger
    {
        $tenantId = $this->resolveTenantId(); // 👈 get your tenant logic here

//        dubai timezone
        $date = now()->setTimezone('Asia/Dubai')->format('Y-m-d');
        $path = storage_path("logs/tenants/{$tenantId}/{$date}.log");

        // Ensure the directory exists
        if (!file_exists(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        $config['path'] = $path;
        return new \Monolog\Logger('tenant', [
            new \Monolog\Handler\StreamHandler($path, $config['level'] ?? 'debug')
        ]);
    }

    protected function resolveTenantId(): string
    {
        // 🔁 Customize this to fit your tenant resolution strategy
//        return session('tenant_id') ?? request()->get('tenant') ?? 'default';
        return "t00001";
    }
}
