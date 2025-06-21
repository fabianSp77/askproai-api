<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Appointment;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AppointmentStats extends BaseWidget
{
    protected static ?int $sort = 1;
    
    protected function getStats(): array
    {
        $today = Carbon::today();
        $startOfWeek = Carbon::now()->startOfWeek();
        $startOfMonth = Carbon::now()->startOfMonth();
        
        // Termine heute
        $appointmentsToday = Appointment::whereDate('starts_at', $today)->count();
        $appointmentsWeek = Appointment::whereBetween('starts_at', [$startOfWeek, Carbon::now()])->count();
        $appointmentsMonth = Appointment::whereBetween('starts_at', [$startOfMonth, Carbon::now()])->count();
        
        // Umsatz berechnen
        $revenueToday = Appointment::whereDate('starts_at', $today)
            ->where('status', 'completed')
            ->with('service')
            ->get()
            ->sum(function($appointment) {
                return $appointment->service?->price ?? 0;
            });
            
        $revenueMonth = Appointment::whereBetween('starts_at', [$startOfMonth, Carbon::now()])
            ->where('status', 'completed')
            ->with('service')
            ->get()
            ->sum(function($appointment) {
                return $appointment->service?->price ?? 0;
            });
        
        // No-Show Rate
        $completedMonth = Appointment::whereBetween('starts_at', [$startOfMonth, Carbon::now()])
            ->whereIn('status', ['completed', 'no_show'])
            ->count();
        $noShowMonth = Appointment::whereBetween('starts_at', [$startOfMonth, Carbon::now()])
            ->where('status', 'no_show')
            ->count();
        $noShowRate = $completedMonth > 0 ? round(($noShowMonth / $completedMonth) * 100, 1) : 0;
        
        // Auslastung heute
        $workingHours = 8; // Standard Arbeitsstunden
        $staffCount = DB::table('staff')->where('is_active', true)->count() ?: 1;
        $totalAvailableMinutes = $workingHours * 60 * $staffCount;
        
        $bookedMinutesToday = Appointment::whereDate('starts_at', $today)
            ->whereNotIn('status', ['cancelled'])
            ->with('service')
            ->get()
            ->sum(function($appointment) {
                return $appointment->service?->default_duration_minutes ?? 60;
            });
            
        $utilizationRate = $totalAvailableMinutes > 0 
            ? round(($bookedMinutesToday / $totalAvailableMinutes) * 100, 1) 
            : 0;
        
        return [
            Stat::make('Termine heute', $appointmentsToday)
                ->description("Diese Woche: {$appointmentsWeek}")
                ->descriptionIcon('heroicon-o-calendar')
                ->chart([7, 5, 10, 3, 15, 4, $appointmentsToday])
                ->color('primary'),
                
            Stat::make('Umsatz heute', number_format($revenueToday / 100, 2, ',', '.') . ' €')
                ->description('Monat: ' . number_format($revenueMonth / 100, 2, ',', '.') . ' €')
                ->descriptionIcon('heroicon-o-currency-euro')
                ->color('success'),
                
            Stat::make('Auslastung', $utilizationRate . '%')
                ->description($utilizationRate > 80 ? 'Hoch' : ($utilizationRate > 50 ? 'Normal' : 'Niedrig'))
                ->descriptionIcon('heroicon-o-chart-bar')
                ->color($utilizationRate > 80 ? 'danger' : ($utilizationRate > 50 ? 'warning' : 'success'))
                ->chart($this->getUtilizationTrend()),
                
            Stat::make('No-Show Rate', $noShowRate . '%')
                ->description($noShowMonth . ' von ' . $completedMonth . ' Terminen')
                ->descriptionIcon('heroicon-o-x-circle')
                ->color($noShowRate > 20 ? 'danger' : ($noShowRate > 10 ? 'warning' : 'success')),
        ];
    }
    
    protected function getUtilizationTrend(): array
    {
        $trend = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $bookedMinutes = Appointment::whereDate('starts_at', $date)
                ->whereNotIn('status', ['cancelled'])
                ->with('service')
                ->get()
                ->sum(function($appointment) {
                    return $appointment->service?->default_duration_minutes ?? 60;
                });
            $trend[] = min(100, round($bookedMinutes / 480 * 100)); // 480 = 8h * 60min
        }
        return $trend;
    }
}