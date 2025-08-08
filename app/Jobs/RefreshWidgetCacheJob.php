<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Services\OptimizedCacheService;

class RefreshWidgetCacheJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 120; // 2 minutes timeout
    public $tries = 2;

    public function __construct(
        private string $widget,
        private ?int $companyId,
        private $callback
    ) {
        $this->onQueue('cache'); // Use dedicated cache queue
    }

    public function handle(OptimizedCacheService $cacheService): void
    {
        try {
            Log::info('Starting background cache refresh', [
                'widget' => $this->widget,
                'company_id' => $this->companyId
            ]);

            $start = microtime(true);
            
            // Execute the callback to refresh data
            $result = call_user_func($this->callback);
            
            $duration = microtime(true) - $start;
            
            // Store the refreshed data
            $key = $cacheService->generateKey($this->widget, $this->companyId);
            $ttl = OptimizedCacheService::TTL_HEAVY_COMPUTATION;
            $tags = ['widgets', 'statistics'];
            
            if ($this->companyId) {
                $tags[] = 'company_data:' . $this->companyId;
            }
            
            Cache::tags($tags)->put($key, $result, $ttl);
            
            Log::info('Background cache refresh completed', [
                'widget' => $this->widget,
                'company_id' => $this->companyId,
                'duration' => $duration
            ]);
            
        } catch (\Exception $e) {
            Log::error('Background cache refresh failed', [
                'widget' => $this->widget,
                'company_id' => $this->companyId,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Background cache refresh job failed permanently', [
            'widget' => $this->widget,
            'company_id' => $this->companyId,
            'error' => $exception->getMessage()
        ]);
    }
}