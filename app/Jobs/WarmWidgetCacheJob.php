<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Services\OptimizedCacheService;

class WarmWidgetCacheJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 60;
    public $tries = 1;

    public function __construct(
        private string $widget,
        private ?int $companyId
    ) {
        $this->onQueue('cache');
    }

    public function handle(): void
    {
        try {
            Log::info('Starting cache warming', [
                'widget' => $this->widget,
                'company_id' => $this->companyId
            ]);

            // Warm cache based on widget type
            match ($this->widget) {
                'dashboard_stats' => $this->warmDashboardStats(),
                'live_calls' => $this->warmLiveCalls(),
                'recent_calls' => $this->warmRecentCalls(),
                'stats_overview' => $this->warmStatsOverview(),
                default => Log::warning('Unknown widget for cache warming', ['widget' => $this->widget])
            };

        } catch (\Exception $e) {
            Log::error('Cache warming failed', [
                'widget' => $this->widget,
                'company_id' => $this->companyId,
                'error' => $e->getMessage()
            ]);
        }
    }

    private function warmDashboardStats(): void
    {
        // Simulate the widget loading to populate cache
        $widget = new \App\Filament\Admin\Widgets\DashboardStats();
        
        // Use reflection to call protected method if needed
        $reflection = new \ReflectionClass($widget);
        $method = $reflection->getMethod('getStats');
        $method->setAccessible(true);
        
        try {
            $method->invoke($widget);
        } catch (\Exception $e) {
            Log::warning('Dashboard stats warming failed', ['error' => $e->getMessage()]);
        }
    }

    private function warmLiveCalls(): void
    {
        $widget = new \App\Filament\Admin\Widgets\LiveCallsWidget();
        $widget->mount();
        $widget->loadActiveCalls();
    }

    private function warmRecentCalls(): void
    {
        $widget = new \App\Filament\Admin\Widgets\RecentCallsWidget();
        $widget->mount();
    }

    private function warmStatsOverview(): void
    {
        $widget = new \App\Filament\Admin\Widgets\StatsOverviewWidget();
        
        $reflection = new \ReflectionClass($widget);
        $method = $reflection->getMethod('getStats');
        $method->setAccessible(true);
        
        try {
            $method->invoke($widget);
        } catch (\Exception $e) {
            Log::warning('Stats overview warming failed', ['error' => $e->getMessage()]);
        }
    }
}