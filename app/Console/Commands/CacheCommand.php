<?php

namespace App\Console\Commands;

use App\Services\Cache\CacheManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CacheCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:lms 
                            {action : The action to perform (clear|warm|stats|flush)}
                            {--tenant= : Tenant ID for tenant-specific operations}
                            {--pattern= : Pattern for key operations}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage LMS cache operations';

    protected CacheManager $cacheManager;

    public function __construct(CacheManager $cacheManager)
    {
        parent::__construct();
        $this->cacheManager = $cacheManager;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');
        $tenantId = $this->option('tenant');
        $pattern = $this->option('pattern');

        switch ($action) {
            case 'clear':
                $this->clearCache($tenantId);
                break;
            case 'warm':
                $this->warmCache($tenantId);
                break;
            case 'stats':
                $this->showCacheStats();
                break;
            case 'flush':
                $this->flushCache();
                break;
            case 'keys':
                $this->showCacheKeys($pattern);
                break;
            case 'expired':
                $this->clearExpiredCache();
                break;
            default:
                $this->error("Unknown action: {$action}");
                $this->line('Available actions: clear, warm, stats, flush, keys, expired');
                return 1;
        }

        return 0;
    }

    /**
     * Clear cache
     */
    protected function clearCache(?string $tenantId): void
    {
        if ($tenantId) {
            $this->info("Clearing cache for tenant {$tenantId}...");
            $this->cacheManager->clearTenantCache((int) $tenantId);
            $this->info("Cache cleared for tenant {$tenantId}");
        } else {
            $this->info("Clearing all cache...");
            $this->cacheManager->flushAll();
            $this->info("All cache cleared");
        }
    }

    /**
     * Warm up cache
     */
    protected function warmCache(?string $tenantId): void
    {
        if ($tenantId) {
            $this->info("Warming up cache for tenant {$tenantId}...");
            $this->cacheManager->warmUpTenantCache((int) $tenantId);
            $this->info("Cache warmed up for tenant {$tenantId}");
        } else {
            $this->error("Tenant ID is required for warming up cache");
        }
    }

    /**
     * Show cache statistics
     */
    protected function showCacheStats(): void
    {
        $this->info("Fetching cache statistics...");
        $stats = $this->cacheManager->getCacheStats();

        if (isset($stats['error'])) {
            $this->error("Error: " . $stats['error']);
            $this->line("Message: " . $stats['message']);
            return;
        }

        $this->table(
            ['Metric', 'Value'],
            [
                ['Redis Version', $stats['redis_version']],
                ['Used Memory', $stats['used_memory']],
                ['Connected Clients', $stats['connected_clients']],
                ['Keyspace Hits', number_format($stats['keyspace_hits'])],
                ['Keyspace Misses', number_format($stats['keyspace_misses'])],
                ['Hit Rate', $stats['hit_rate'] . '%'],
                ['Total Keys', number_format($stats['total_keys'])],
            ]
        );
    }

    /**
     * Flush all cache
     */
    protected function flushCache(): void
    {
        if ($this->confirm('Are you sure you want to flush all cache? This cannot be undone.')) {
            $this->info("Flushing all cache...");
            $this->cacheManager->flushAll();
            $this->info("All cache flushed");
        } else {
            $this->info("Cache flush cancelled");
        }
    }

    /**
     * Show cache keys
     */
    protected function showCacheKeys(?string $pattern): void
    {
        $pattern = $pattern ?? '*';
        $this->info("Fetching cache keys with pattern: {$pattern}");
        
        $keys = $this->cacheManager->getCacheKeysByPattern($pattern);
        
        if (empty($keys)) {
            $this->info("No keys found matching pattern: {$pattern}");
            return;
        }

        $this->info("Found " . count($keys) . " keys:");
        
        // Show first 50 keys to avoid overwhelming output
        $displayKeys = array_slice($keys, 0, 50);
        foreach ($displayKeys as $key) {
            $this->line("  - {$key}");
        }
        
        if (count($keys) > 50) {
            $this->info("... and " . (count($keys) - 50) . " more keys");
        }
    }

    /**
     * Clear expired cache
     */
    protected function clearExpiredCache(): void
    {
        $this->info("Clearing expired cache entries...");
        $this->cacheManager->clearExpiredCache();
        $this->info("Expired cache entries cleared");
    }
}
