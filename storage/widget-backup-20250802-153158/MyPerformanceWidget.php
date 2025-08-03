<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Appointment;
use App\Models\Staff;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class MyPerformanceWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected function getStats(): array
    {
        $user = Auth::user();
        $staff = Staff::where('user_id', $user->id)->first();
        
        if (!$staff) {
            return [
                Stat::make('Diese Woche', '0 Termine'),
                Stat::make('Umsatz', '0,00 €'),
                Stat::make('Bewertung', 'N/A'),
            ];
        }

        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();
        
        // Termine diese Woche
        $appointmentsThisWeek = Appointment::where('staff_id', $staff->id)
            ->whereBetween('starts_at', [$startOfWeek, $endOfWeek])
            ->whereIn('status', ['completed', 'confirmed', 'scheduled'])
            ->count();
        
        // Termine letzte Woche
        $appointmentsLastWeek = Appointment::where('staff_id', $staff->id)
            ->whereBetween('starts_at', [
                $startOfWeek->copy()->subWeek(),
                $endOfWeek->copy()->subWeek()
            ])
            ->whereIn('status', ['completed', 'confirmed', 'scheduled'])
            ->count();
        
        // Trend
        $appointmentTrend = $appointmentsLastWeek > 0 
            ? round((($appointmentsThisWeek - $appointmentsLastWeek) / $appointmentsLastWeek) * 100)
            : 0;
        
        // Umsatz diese Woche
        $revenueThisWeek = Appointment::where('staff_id', $staff->id)
            ->whereBetween('starts_at', [$startOfWeek, $endOfWeek])
            ->whereIn('status', ['completed'])
            ->sum('price');
        
        // Umsatz letzte Woche
        $revenueLastWeek = Appointment::where('staff_id', $staff->id)
            ->whereBetween('starts_at', [
                $startOfWeek->copy()->subWeek(),
                $endOfWeek->copy()->subWeek()
            ])
            ->whereIn('status', ['completed'])
            ->sum('price');
        
        // Durchschnittsbewertung (simuliert)
        $rating = 4.7; // TODO: Implement real rating system
        $ratingTrend = '+0.2';
        
        // Chart Daten (letzte 7 Tage)
        $dailyAppointments = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $count = Appointment::where('staff_id', $staff->id)
                ->whereDate('starts_at', $date)
                ->whereIn('status', ['completed', 'confirmed', 'scheduled'])
                ->count();
            $dailyAppointments[] = $count;
        }

        return [
            Stat::make('Diese Woche', $appointmentsThisWeek . ' Termine')
                ->description($appointmentTrend > 0 ? '+' . $appointmentTrend . '% vs. letzte Woche' : $appointmentTrend . '% vs. letzte Woche')
                ->descriptionIcon($appointmentTrend > 0 ? 'heroicon-o-arrow-trending-up' : 'heroicon-o-arrow-trending-down')
                ->color($appointmentTrend >= 0 ? 'success' : 'danger')
                ->chart($dailyAppointments),
                
            Stat::make('Umsatz', number_format($revenueThisWeek, 2, ',', '.') . ' €')
                ->description('Ziel: ' . number_format($revenueThisWeek * 1.2, 2, ',', '.') . ' €')
                ->color($revenueThisWeek >= $revenueLastWeek ? 'success' : 'warning'),
                
            Stat::make('Bewertung', $rating . ' ⭐')
                ->description($ratingTrend . ' diese Woche')
                ->color($rating >= 4.5 ? 'success' : ($rating >= 4.0 ? 'warning' : 'danger')),
        ];
    }
}