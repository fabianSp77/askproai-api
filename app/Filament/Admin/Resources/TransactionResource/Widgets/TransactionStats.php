<?php

namespace App\Filament\Admin\Resources\TransactionResource\Widgets;

use App\Models\Transaction;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class TransactionStats extends BaseWidget
{
    protected static ?string $pollingInterval = '30s'; // Increased due to caching
    
    protected function getStats(): array
    {
        $today = Carbon::today();
        $thisMonth = Carbon::now()->startOfMonth();
        
        // Cache key generation
        $cacheKeyPrefix = 'transaction_stats_' . $today->format('Y-m-d');
        
        // Heutiger Umsatz (cached for 60 seconds)
        $todayStats = Cache::remember($cacheKeyPrefix . '_today', 60, function () use ($today) {
            $credits = Transaction::whereDate('created_at', $today)
                ->where('amount_cents', '>', 0)
                ->sum('amount_cents');
            
            $debits = Transaction::whereDate('created_at', $today)
                ->where('amount_cents', '<', 0)
                ->sum('amount_cents');
            
            $count = Transaction::whereDate('created_at', $today)->count();
            
            return [
                'credits' => $credits,
                'debits' => $debits,
                'net' => $credits + $debits,
                'count' => $count,
            ];
        });
        
        // Monatlicher Umsatz (cached for 5 minutes)
        $monthStats = Cache::remember($cacheKeyPrefix . '_month', 300, function () use ($thisMonth) {
            $credits = Transaction::where('created_at', '>=', $thisMonth)
                ->where('amount_cents', '>', 0)
                ->sum('amount_cents');
            
            $debits = Transaction::where('created_at', '>=', $thisMonth)
                ->where('amount_cents', '<', 0)
                ->sum('amount_cents');
            
            $count = Transaction::where('created_at', '>=', $thisMonth)->count();
            
            $avg = Transaction::where('created_at', '>=', $thisMonth)
                ->where('amount_cents', '>', 0)
                ->avg('amount_cents') ?? 0;
            
            return [
                'credits' => $credits,
                'debits' => $debits,
                'net' => $credits + $debits,
                'count' => $count,
                'avg' => $avg,
            ];
        });
        
        return [
            Stat::make('Heutiger Netto-Umsatz', number_format($todayStats['net'] / 100, 2) . ' €')
                ->description(sprintf(
                    '+%s / -%s',
                    number_format($todayStats['credits'] / 100, 2),
                    number_format(abs($todayStats['debits']) / 100, 2)
                ))
                ->descriptionIcon($todayStats['net'] >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->chart($this->getCachedHourlyChart())
                ->color($todayStats['net'] >= 0 ? 'success' : 'danger'),
            
            Stat::make('Monatlicher Umsatz', number_format($monthStats['net'] / 100, 2) . ' €')
                ->description(sprintf(
                    '+%s / -%s',
                    number_format($monthStats['credits'] / 100, 2),
                    number_format(abs($monthStats['debits']) / 100, 2)
                ))
                ->descriptionIcon('heroicon-m-calendar')
                ->chart($this->getCachedDailyChart())
                ->color($monthStats['net'] >= 0 ? 'success' : 'warning'),
            
            Stat::make('Transaktionen heute', $todayStats['count'])
                ->description($monthStats['count'] . ' diesen Monat')
                ->descriptionIcon('heroicon-m-arrow-path')
                ->chart($this->getCachedTransactionCountChart())
                ->color('info'),
            
            Stat::make('Ø Aufladung', number_format($monthStats['avg'] / 100, 2) . ' €')
                ->description('Diesen Monat')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('primary'),
        ];
    }
    
    protected function getHourlyChart(string $period): array
    {
        $data = [];
        $start = Carbon::today();
        
        for ($i = 0; $i < 24; $i++) {
            $hour = $start->copy()->addHours($i);
            $amount = Transaction::whereBetween('created_at', [
                $hour,
                $hour->copy()->addHour()
            ])->sum('amount_cents');
            
            $data[] = $amount / 100;
        }
        
        return $data;
    }
    
    protected function getDailyChart(): array
    {
        $data = [];
        $start = Carbon::now()->startOfMonth();
        $days = Carbon::now()->day;
        
        for ($i = 0; $i < $days; $i++) {
            $day = $start->copy()->addDays($i);
            $amount = Transaction::whereDate('created_at', $day)->sum('amount_cents');
            $data[] = $amount / 100;
        }
        
        return $data;
    }
    
    protected function getTransactionCountChart(): array
    {
        $data = [];
        
        for ($i = 6; $i >= 0; $i--) {
            $day = Carbon::today()->subDays($i);
            $count = Transaction::whereDate('created_at', $day)->count();
            $data[] = $count;
        }
        
        return $data;
    }
    
    /**
     * Cached version of getHourlyChart
     */
    protected function getCachedHourlyChart(): array
    {
        $cacheKey = 'transaction_chart_hourly_' . Carbon::today()->format('Y-m-d');
        
        return Cache::remember($cacheKey, 300, function () {
            return $this->getHourlyChart('today');
        });
    }
    
    /**
     * Cached version of getDailyChart
     */
    protected function getCachedDailyChart(): array
    {
        $cacheKey = 'transaction_chart_daily_' . Carbon::now()->format('Y-m');
        
        return Cache::remember($cacheKey, 600, function () {
            return $this->getDailyChart();
        });
    }
    
    /**
     * Cached version of getTransactionCountChart
     */
    protected function getCachedTransactionCountChart(): array
    {
        $cacheKey = 'transaction_chart_count_' . Carbon::today()->format('Y-m-d');
        
        return Cache::remember($cacheKey, 300, function () {
            return $this->getTransactionCountChart();
        });
    }
    
    /**
     * Clear cache when new transactions are created
     * This can be called from the TransactionObserver
     */
    public static function clearCache(): void
    {
        $today = Carbon::today();
        $cacheKeyPrefix = 'transaction_stats_' . $today->format('Y-m-d');
        
        Cache::forget($cacheKeyPrefix . '_today');
        Cache::forget($cacheKeyPrefix . '_month');
        Cache::forget('transaction_chart_hourly_' . $today->format('Y-m-d'));
        Cache::forget('transaction_chart_daily_' . Carbon::now()->format('Y-m'));
        Cache::forget('transaction_chart_count_' . $today->format('Y-m-d'));
    }
}