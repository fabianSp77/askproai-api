<?php

namespace App\Filament\Admin\Resources\CustomerResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Customer;
use App\Models\Appointment;
use Carbon\Carbon;

class CustomerInsightsWidget extends BaseWidget
{
    public ?Customer $record = null;
    
    protected function getStats(): array
    {
        if (!$this->record) {
            return [];
        }
        
        $customer = $this->record;
        
        // Calculate lifetime value (sum of all completed appointment prices)
        $lifetimeValue = $customer->appointments()
            ->where('status', 'completed')
            ->join('services', 'appointments.service_id', '=', 'services.id')
            ->sum('services.price') / 100; // Convert cents to euros
        
        // Calculate average spend per visit
        $completedAppointments = $customer->appointments()
            ->where('status', 'completed')
            ->count();
        $avgSpend = $completedAppointments > 0 ? $lifetimeValue / $completedAppointments : 0;
        
        // Calculate retention metrics
        $firstAppointment = $customer->appointments()
            ->orderBy('starts_at', 'asc')
            ->first();
        $lastAppointment = $customer->appointments()
            ->orderBy('starts_at', 'desc')
            ->first();
        
        $customerLifespanDays = $firstAppointment && $lastAppointment 
            ? $firstAppointment->starts_at->diffInDays($lastAppointment->starts_at)
            : 0;
        
        // Calculate visit frequency (average days between appointments)
        $appointmentDates = $customer->appointments()
            ->where('status', 'completed')
            ->orderBy('starts_at', 'asc')
            ->pluck('starts_at');
        
        $avgDaysBetweenVisits = 0;
        if ($appointmentDates->count() > 1) {
            $totalDays = 0;
            for ($i = 1; $i < $appointmentDates->count(); $i++) {
                $totalDays += $appointmentDates[$i]->diffInDays($appointmentDates[$i - 1]);
            }
            $avgDaysBetweenVisits = round($totalDays / ($appointmentDates->count() - 1));
        }
        
        // Calculate no-show rate
        $totalAppointments = $customer->appointments()->count();
        $noShows = $customer->appointments()->where('status', 'no_show')->count();
        $noShowRate = $totalAppointments > 0 ? round(($noShows / $totalAppointments) * 100, 1) : 0;
        
        // Predict next visit
        $nextVisitPrediction = $lastAppointment && $avgDaysBetweenVisits > 0
            ? $lastAppointment->starts_at->addDays($avgDaysBetweenVisits)
            : null;
        
        $isAtRisk = $nextVisitPrediction && $nextVisitPrediction->isPast();
        
        return [
            Stat::make('Gesamtumsatz', '€ ' . number_format($lifetimeValue, 2, ',', '.'))
                ->description($completedAppointments . ' abgeschlossene Termine')
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->color('success')
                ->chart($this->getRevenueChart()),
                
            Stat::make('Ø Ausgaben', '€ ' . number_format($avgSpend, 2, ',', '.'))
                ->description('Pro Besuch')
                ->descriptionIcon('heroicon-m-currency-euro')
                ->color('info'),
                
            Stat::make('Besuchsfrequenz', $avgDaysBetweenVisits > 0 ? "alle {$avgDaysBetweenVisits} Tage" : 'Erster Besuch')
                ->description($isAtRisk ? 'Überfällig!' : 'Regelmäßig')
                ->descriptionIcon($isAtRisk ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-calendar')
                ->color($isAtRisk ? 'danger' : 'success'),
                
            Stat::make('No-Show Rate', "{$noShowRate}%")
                ->description("{$noShows} von {$totalAppointments} Terminen")
                ->descriptionIcon('heroicon-m-user-minus')
                ->color($noShowRate > 20 ? 'danger' : ($noShowRate > 10 ? 'warning' : 'success')),
        ];
    }
    
    protected function getRevenueChart(): array
    {
        // Get revenue for last 6 months
        $revenues = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $revenue = $this->record->appointments()
                ->where('status', 'completed')
                ->whereMonth('starts_at', $month->month)
                ->whereYear('starts_at', $month->year)
                ->join('services', 'appointments.service_id', '=', 'services.id')
                ->sum('services.price') / 100;
            $revenues[] = $revenue;
        }
        
        return $revenues;
    }
}