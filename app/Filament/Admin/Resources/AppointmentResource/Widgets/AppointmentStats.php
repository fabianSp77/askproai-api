<?php

namespace App\Filament\Admin\Resources\AppointmentResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Support\Number;

class AppointmentStats extends BaseWidget
{
    protected function getStats(): array
    {
        $todayAppointments = Appointment::whereDate('starts_at', today())->count();
        $weekAppointments = Appointment::whereBetween('starts_at', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ])->count();
        
        $completedToday = Appointment::whereDate('starts_at', today())
            ->where('status', 'completed')
            ->count();
        
        $pendingAppointments = Appointment::where('status', 'pending')
            ->where('starts_at', '>=', now())
            ->count();
            
        $revenue = Appointment::join('services', 'appointments.service_id', '=', 'services.id')
            ->whereBetween('appointments.starts_at', [
                now()->startOfMonth(),
                now()->endOfMonth()
            ])
            ->where('appointments.status', '!=', 'cancelled')
            ->sum('services.price');
        
        return [
            Stat::make('Termine heute', $todayAppointments)
                ->description($completedToday . ' abgeschlossen')
                ->descriptionIcon('heroicon-s-check-circle')
                ->chart([7, 3, 4, 5, 6, 8, $todayAppointments])
                ->color('primary'),
                
            Stat::make('Diese Woche', $weekAppointments)
                ->description('Termine geplant')
                ->descriptionIcon('heroicon-s-calendar')
                ->chart([12, 15, 14, 18, 16, 20, $weekAppointments])
                ->color('success'),
                
            Stat::make('Ausstehend', $pendingAppointments)
                ->description('Zu bestätigen')
                ->descriptionIcon('heroicon-s-clock')
                ->color('warning'),
                
            Stat::make('Umsatz (Monat)', Number::currency($revenue / 100, 'EUR'))
                ->description('Ohne abgesagte Termine')
                ->descriptionIcon('heroicon-s-currency-euro')
                ->chart([
                    $revenue * 0.7 / 100,
                    $revenue * 0.8 / 100,
                    $revenue * 0.6 / 100,
                    $revenue * 0.9 / 100,
                    $revenue * 0.85 / 100,
                    $revenue * 0.95 / 100,
                    $revenue / 100,
                ])
                ->color('success'),
        ];
    }
}