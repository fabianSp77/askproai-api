<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Call;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class CallKpiWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '5s';
    
    public static function canView(): bool
    {
        return true; // Always show this widget
    }
    
    protected function getStats(): array
    {
        $company = auth()->user()->company;
        
        if (!$company) {
            return [];
        }

        // Today's calls
        $todayCount = Call::where('company_id', $company->id)
            ->whereDate('start_timestamp', today())
            ->count();
            
        // This week's calls
        $weekCount = Call::where('company_id', $company->id)
            ->whereBetween('start_timestamp', [
                now()->startOfWeek(),
                now()->endOfWeek()
            ])
            ->count();
            
        // Average call duration
        $avgDuration = Call::where('company_id', $company->id)
            ->whereNotNull('duration_sec')
            ->where('duration_sec', '>', 0)
            ->avg('duration_sec');
            
        $avgDurationFormatted = $avgDuration 
            ? gmdate('i:s', $avgDuration) 
            : '0:00';
            
        // Conversion rate (calls that resulted in appointments)
        $totalCalls = Call::where('company_id', $company->id)
            ->where('start_timestamp', '>=', now()->startOfMonth())
            ->count();
            
        $callsWithAppointments = Call::where('company_id', $company->id)
            ->where('start_timestamp', '>=', now()->startOfMonth())
            ->whereNotNull('appointment_id')
            ->count();
            
        $conversionRate = $totalCalls > 0 
            ? round(($callsWithAppointments / $totalCalls) * 100) 
            : 0;

        return [
            Stat::make('Anrufe heute', $todayCount)
                ->description($todayCount === 1 ? 'Anruf' : 'Anrufe')
                ->descriptionIcon('heroicon-m-phone-arrow-down-left')
                ->color('primary')
                ->extraAttributes([
                    'class' => 'call-stat-gradient-primary'
                ]),
                
            Stat::make('Diese Woche', $weekCount)
                ->description($weekCount === 1 ? 'Anruf empfangen' : 'Anrufe empfangen')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('info')
                ->extraAttributes([
                    'class' => 'call-stat-gradient-info'
                ]),
                
            Stat::make('Ø Gesprächsdauer', $avgDurationFormatted)
                ->description('Minuten:Sekunden')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning')
                ->extraAttributes([
                    'class' => 'call-stat-gradient-warning'
                ]),
                
            Stat::make('Konversionsrate', $conversionRate . '%')
                ->description('Termine aus Anrufen')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color($conversionRate >= 20 ? 'success' : ($conversionRate >= 10 ? 'warning' : 'danger'))
                ->extraAttributes([
                    'class' => $conversionRate >= 20 ? 'call-stat-gradient-success' : ($conversionRate >= 10 ? 'call-stat-gradient-warning' : 'stat-card-gradient-danger')
                ]),
        ];
    }
    
    protected function getColumns(): int
    {
        return 4;
    }
}