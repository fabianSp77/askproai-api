<?php

namespace App\Filament\Admin\Resources\AppointmentResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Appointment;
use Carbon\Carbon;

class AppointmentStatsWidget extends BaseWidget
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
            
        $noShowsThisWeek = Appointment::whereBetween('starts_at', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ])
        ->where('status', 'no_show')
        ->count();
        
        // Durchschnittlicher Service-Preis
        $avgPrice = Appointment::whereDate('starts_at', today())
            ->where('status', '!=', 'cancelled')
            ->avg('price') ?? 0;
            
        // Auslastung heute
        $totalSlots = 8 * 4; // 8 Stunden * 4 Slots pro Stunde (Beispiel)
        $bookedSlots = $todayAppointments;
        $occupancyRate = $totalSlots > 0 ? round(($bookedSlots / $totalSlots) * 100) : 0;
        
        // Conversion Rate (Calls zu Appointments)
        $callsToday = \App\Models\Call::whereDate('created_at', today())->count();
        $appointmentsFromCalls = Appointment::whereDate('created_at', today())
            ->whereNotNull('call_id')
            ->count();
        $conversionRate = $callsToday > 0 ? round(($appointmentsFromCalls / $callsToday) * 100) : 0;
        
        return [
            Stat::make('Termine heute', $todayAppointments)
                ->description($completedToday . ' abgeschlossen')
                ->descriptionIcon('heroicon-s-check-circle')
                ->chart($this->getAppointmentChart())
                ->color('primary'),
                
            Stat::make('Diese Woche', $weekAppointments)
                ->description($noShowsThisWeek . ' No-Shows')
                ->descriptionIcon($noShowsThisWeek > 0 ? 'heroicon-s-exclamation-triangle' : 'heroicon-s-check')
                ->color($noShowsThisWeek > 0 ? 'warning' : 'success'),
                
            Stat::make('Ø Service-Preis', number_format($avgPrice / 100, 2, ',', '.') . ' €')
                ->description('pro Termin heute')
                ->descriptionIcon('heroicon-s-currency-euro')
                ->color('success'),
                
            Stat::make('Auslastung', $occupancyRate . '%')
                ->description('der verfügbaren Slots')
                ->descriptionIcon('heroicon-s-chart-bar')
                ->chart($this->getOccupancyChart())
                ->color($occupancyRate > 80 ? 'success' : ($occupancyRate > 50 ? 'warning' : 'danger')),
                
            Stat::make('Call → Termin', $conversionRate . '%')
                ->description($appointmentsFromCalls . ' aus Anrufen')
                ->descriptionIcon('heroicon-s-phone-arrow-up-right')
                ->color($conversionRate > 30 ? 'success' : 'warning'),
        ];
    }
    
    private function getAppointmentChart(): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $data[] = Appointment::whereDate('starts_at', today()->subDays($i))->count();
        }
        return $data;
    }
    
    private function getOccupancyChart(): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = today()->subDays($i);
            $appointments = Appointment::whereDate('starts_at', $date)
                ->where('status', '!=', 'cancelled')
                ->count();
            $totalSlots = 32; // Beispiel
            $data[] = $totalSlots > 0 ? round(($appointments / $totalSlots) * 100) : 0;
        }
        return $data;
    }
}