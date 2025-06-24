<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Appointment;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class AppointmentKpiWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '30s';
    
    protected function getStats(): array
    {
        $company = auth()->user()->company;
        
        if (!$company) {
            return [];
        }

        // Today's appointments
        $todayCount = Appointment::where('company_id', $company->id)
            ->whereDate('starts_at', today())
            ->count();
            
        // This week's appointments
        $weekCount = Appointment::where('company_id', $company->id)
            ->whereBetween('starts_at', [
                now()->startOfWeek(),
                now()->endOfWeek()
            ])
            ->count();
            
        // Pending confirmations
        $pendingCount = Appointment::where('company_id', $company->id)
            ->where('status', 'pending')
            ->where('starts_at', '>', now())
            ->count();
            
        // Completion rate this month
        $monthStart = now()->startOfMonth();
        $completedThisMonth = Appointment::where('company_id', $company->id)
            ->where('status', 'completed')
            ->where('starts_at', '>=', $monthStart)
            ->count();
            
        $totalThisMonth = Appointment::where('company_id', $company->id)
            ->where('starts_at', '>=', $monthStart)
            ->where('starts_at', '<', now())
            ->count();
            
        $completionRate = $totalThisMonth > 0 
            ? round(($completedThisMonth / $totalThisMonth) * 100) 
            : 0;

        return [
            Stat::make('Heute', $todayCount)
                ->description($todayCount === 1 ? 'Termin' : 'Termine')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('primary')
                ->extraAttributes([
                    'class' => 'stat-card-gradient-primary'
                ]),
                
            Stat::make('Diese Woche', $weekCount)
                ->description($weekCount === 1 ? 'Termin geplant' : 'Termine geplant')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('info')
                ->extraAttributes([
                    'class' => 'stat-card-gradient-info'
                ]),
                
            Stat::make('Zu bestätigen', $pendingCount)
                ->description($pendingCount === 1 ? 'Termin offen' : 'Termine offen')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($pendingCount > 0 ? 'warning' : 'success')
                ->extraAttributes([
                    'class' => $pendingCount > 0 ? 'stat-card-gradient-warning' : 'stat-card-gradient-success'
                ]),
                
            Stat::make('Erfolgsquote', $completionRate . '%')
                ->description('Diesen Monat')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color($completionRate >= 80 ? 'success' : ($completionRate >= 60 ? 'warning' : 'danger'))
                ->extraAttributes([
                    'class' => $completionRate >= 80 ? 'stat-card-gradient-success' : ($completionRate >= 60 ? 'stat-card-gradient-warning' : 'stat-card-gradient-danger')
                ]),
        ];
    }
    
    protected function getColumns(): int
    {
        return 4;
    }
}