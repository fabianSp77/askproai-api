<?php

namespace App\Filament\Widgets;

use App\Models\Service;
use App\Services\CalcomApiRateLimiter;
use Carbon\Carbon;
use Filament\Widgets\Widget;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;

class CalcomSyncStatusWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected int | string | array $columnSpan = 'full';

    protected function getHeading(): ?string
    {
        return 'Cal.com Sync Status';
    }

    protected function getStats(): array
    {
        try {
            $totalServices = Service::count();
            $syncedServices = Service::where('sync_status', 'synced')->count();
            $pendingServices = Service::where('sync_status', 'pending')->count();
            $errorServices = Service::where('sync_status', 'error')->count();

            // Last sync time
            $lastSync = Service::whereNotNull('last_calcom_sync')
                ->orderBy('last_calcom_sync', 'desc')
                ->value('last_calcom_sync');

            $lastSyncFormatted = $lastSync
                ? Carbon::parse($lastSync)->diffForHumans()
                : 'Never';

            // Success rate (last 24 hours)
            $recentSyncs = Service::where('last_calcom_sync', '>', now()->subDay())
                ->select('sync_status')
                ->get();

            $successRate = $recentSyncs->isNotEmpty()
                ? round(($recentSyncs->where('sync_status', 'synced')->count() / $recentSyncs->count()) * 100, 1)
                : 100;

            // Rate limiter status
            $rateLimiter = new CalcomApiRateLimiter();
            $remainingRequests = $rateLimiter->getRemainingRequests();

            // Queue status
            $queuedJobs = DB::table('jobs')
                ->where('queue', 'calcom-sync')
                ->count();

            return [
            Stat::make('Total Services', $totalServices)
                ->description('All Cal.com synced services')
                ->descriptionIcon('heroicon-m-server')
                ->color('primary'),

            Stat::make('Synced', $syncedServices)
                ->description($lastSyncFormatted)
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success')
                ->chart($this->getSyncChart()),

            Stat::make('Pending', $pendingServices)
                ->description($queuedJobs . ' in queue')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('Errors', $errorServices)
                ->description($errorServices > 0 ? 'Needs attention' : 'All good')
                ->descriptionIcon($errorServices > 0 ? 'heroicon-m-x-circle' : 'heroicon-m-check')
                ->color($errorServices > 0 ? 'danger' : 'success'),

            Stat::make('Success Rate', $successRate . '%')
                ->description('Last 24 hours')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color($successRate >= 95 ? 'success' : ($successRate >= 80 ? 'warning' : 'danger')),

            Stat::make('API Rate Limit', $remainingRequests . '/60')
                ->description('Requests remaining this minute')
                ->descriptionIcon('heroicon-m-signal')
                ->color($remainingRequests > 30 ? 'success' : ($remainingRequests > 10 ? 'warning' : 'danger')),
            ];
        } catch (QueryException $exception) {
            // Table doesn't exist yet - log and return empty stats instead of crashing dashboard
            if ($exception->getCode() === '42S02') {
                Log::warning('[CalcomSyncStatusWidget] Required table not found. Dashboard tables may still be migrating.', [
                    'error' => $exception->getMessage(),
                ]);
                return [];
            }
            // Re-throw other database exceptions
            throw $exception;
        }
    }

    protected function getSyncChart(): array
    {
        try {
            // Get sync history for the last 7 days
            $syncHistory = Service::where('last_calcom_sync', '>', now()->subDays(7))
                ->select(DB::raw('DATE(last_calcom_sync) as date'), DB::raw('COUNT(*) as count'))
                ->groupBy('date')
                ->orderBy('date')
                ->pluck('count')
                ->toArray();

            // Pad with zeros if less than 7 days
            while (count($syncHistory) < 7) {
                array_unshift($syncHistory, 0);
            }

            return $syncHistory;
        } catch (QueryException $exception) {
            // Return empty array on table not found instead of crashing
            if ($exception->getCode() === '42S02') {
                return array_fill(0, 7, 0);
            }
            throw $exception;
        }
    }

    /**
     * Polling interval (null = no polling)
     */
    protected static ?string $pollingInterval = '300s';
}