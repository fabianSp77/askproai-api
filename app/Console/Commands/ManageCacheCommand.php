<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\OptimizedCacheService;
use App\Models\Company;

class ManageCacheCommand extends Command
{
    protected $signature = 'cache:manage 
                            {action : Action to perform (clear|warm|stats|invalidate)}
                            {--widget= : Specific widget to target}
                            {--company= : Company ID to target}
                            {--all : Apply to all companies}';

    protected $description = 'Manage widget cache system';

    public function handle(OptimizedCacheService $cacheService): int
    {
        $action = $this->argument('action');
        $widget = $this->option('widget');
        $companyId = $this->option('company');
        $allCompanies = $this->option('all');

        return match ($action) {
            'clear' => $this->clearCache($cacheService, $widget, $companyId, $allCompanies),
            'warm' => $this->warmCache($cacheService, $widget, $companyId, $allCompanies),
            'stats' => $this->showStats($cacheService),
            'invalidate' => $this->invalidateCache($cacheService, $widget, $companyId, $allCompanies),
            default => $this->error("Unknown action: {$action}")
        };
    }

    private function clearCache(OptimizedCacheService $cacheService, ?string $widget, ?string $companyId, bool $allCompanies): int
    {
        if ($allCompanies) {
            $companies = Company::all();
            $this->info('Clearing cache for all companies...');
            
            foreach ($companies as $company) {
                $cacheService->invalidateCompany($company->id);
            }
            
            $this->info("Cleared cache for {$companies->count()} companies");
            return 0;
        }

        if ($widget && $companyId) {
            $cacheService->invalidateWidget($widget, (int)$companyId);
            $this->info("Cleared cache for widget '{$widget}' of company {$companyId}");
            return 0;
        }

        if ($companyId) {
            $cacheService->invalidateCompany((int)$companyId);
            $this->info("Cleared all cache for company {$companyId}");
            return 0;
        }

        // Clear all widget cache
        $cacheService->invalidateLiveData();
        $this->info('Cleared all live data cache');
        return 0;
    }

    private function warmCache(OptimizedCacheService $cacheService, ?string $widget, ?string $companyId, bool $allCompanies): int
    {
        if ($allCompanies) {
            $companies = Company::all();
            $this->info('Warming cache for all companies...');
            
            foreach ($companies as $company) {
                $cacheService->warmCriticalWidgets($company->id);
            }
            
            $this->info("Initiated cache warming for {$companies->count()} companies");
            return 0;
        }

        if ($companyId) {
            $cacheService->warmCriticalWidgets((int)$companyId);
            $this->info("Initiated cache warming for company {$companyId}");
            return 0;
        }

        // Warm cache for all companies
        $cacheService->warmCriticalWidgets(null);
        $this->info('Initiated cache warming for all companies');
        return 0;
    }

    private function showStats(OptimizedCacheService $cacheService): int
    {
        $stats = $cacheService->getStats();
        
        $this->info('Cache Statistics:');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Widgets', $stats['total_widgets']],
                ['Cache Hits', $stats['cache_hits']],
                ['Cache Misses', $stats['cache_misses']],
                ['Average Response Time', $stats['avg_response_time'] . 'ms'],
                ['Slow Queries', $stats['slow_queries']],
            ]
        );

        // Show cache hit ratio
        $total = $stats['cache_hits'] + $stats['cache_misses'];
        if ($total > 0) {
            $hitRatio = round(($stats['cache_hits'] / $total) * 100, 2);
            $this->info("Cache Hit Ratio: {$hitRatio}%");
        }

        return 0;
    }

    private function invalidateCache(OptimizedCacheService $cacheService, ?string $widget, ?string $companyId, bool $allCompanies): int
    {
        return $this->clearCache($cacheService, $widget, $companyId, $allCompanies);
    }
}