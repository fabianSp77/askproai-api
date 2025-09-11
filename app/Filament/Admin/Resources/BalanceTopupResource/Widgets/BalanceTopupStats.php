<?php

namespace App\Filament\Admin\Resources\BalanceTopupResource\Widgets;

use App\Models\BalanceTopup;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;

class BalanceTopupStats extends BaseWidget
{
    protected static ?string $pollingInterval = '10s';
    
    protected function getStats(): array
    {
        $today = Carbon::today();
        $thisMonth = Carbon::now()->startOfMonth();
        
        // Heutige Aufladungen
        $todayTopups = BalanceTopup::whereDate('created_at', $today)
            ->where('status', 'succeeded')
            ->sum('amount');
        
        $todayCount = BalanceTopup::whereDate('created_at', $today)
            ->where('status', 'succeeded')
            ->count();
        
        // Monatliche Aufladungen
        $monthTopups = BalanceTopup::where('created_at', '>=', $thisMonth)
            ->where('status', 'succeeded')
            ->sum('amount');
        
        $monthCount = BalanceTopup::where('created_at', '>=', $thisMonth)
            ->where('status', 'succeeded')
            ->count();
        
        // Ausstehende Aufladungen
        $pendingTopups = BalanceTopup::whereIn('status', ['pending', 'processing'])
            ->sum('amount');
        
        $pendingCount = BalanceTopup::whereIn('status', ['pending', 'processing'])
            ->count();
        
        // Erfolgsrate
        $totalAttempts = BalanceTopup::where('created_at', '>=', $thisMonth)->count();
        $successfulAttempts = BalanceTopup::where('created_at', '>=', $thisMonth)
            ->where('status', 'succeeded')
            ->count();
        
        $successRate = $totalAttempts > 0 ? round(($successfulAttempts / $totalAttempts) * 100, 1) : 0;
        
        return [
            Stat::make('Heutige Aufladungen', number_format($todayTopups, 2) . ' €')
                ->description($todayCount . ' Transaktionen')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->chart($this->getHourlyChart())
                ->color('success'),
            
            Stat::make('Monatliche Aufladungen', number_format($monthTopups, 2) . ' €')
                ->description($monthCount . ' Transaktionen')
                ->descriptionIcon('heroicon-m-calendar')
                ->chart($this->getDailyChart())
                ->color('primary'),
            
            Stat::make('Ausstehend', number_format($pendingTopups, 2) . ' €')
                ->description($pendingCount . ' Aufladungen')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),
            
            Stat::make('Erfolgsrate', $successRate . '%')
                ->description('Diesen Monat')
                ->descriptionIcon($successRate >= 90 ? 'heroicon-m-check-circle' : 'heroicon-m-exclamation-triangle')
                ->color($successRate >= 90 ? 'success' : ($successRate >= 70 ? 'warning' : 'danger')),
        ];
    }
    
    protected function getHourlyChart(): array
    {
        $data = [];
        $start = Carbon::today();
        
        for ($i = 0; $i < 24; $i++) {
            $hour = $start->copy()->addHours($i);
            $amount = BalanceTopup::whereBetween('created_at', [
                $hour,
                $hour->copy()->addHour()
            ])
            ->where('status', 'succeeded')
            ->sum('amount');
            
            $data[] = $amount;
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
            $amount = BalanceTopup::whereDate('created_at', $day)
                ->where('status', 'succeeded')
                ->sum('amount');
            $data[] = $amount;
        }
        
        return $data;
    }
}