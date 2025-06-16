<?php

namespace App\Filament\Admin\Resources\CustomerResource\Widgets;

use App\Models\Customer;
use App\Models\Appointment;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Model;

class CustomerStatsWidget extends BaseWidget
{
    public ?Model $record = null;
    
    protected function getStats(): array
    {
        if (!$this->record) {
            return [];
        }
        
        $appointmentStats = $this->record->appointments()
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = "cancelled" THEN 1 ELSE 0 END) as cancelled,
                SUM(CASE WHEN status = "no_show" THEN 1 ELSE 0 END) as no_shows
            ')
            ->first();
            
        $revenue = $this->record->calculateTotalRevenue();
        $avgAppointmentValue = $appointmentStats->completed > 0 ? $revenue / $appointmentStats->completed : 0;
        
        $nextAppointment = $this->record->appointments()
            ->where('starts_at', '>', now())
            ->where('status', 'scheduled')
            ->orderBy('starts_at')
            ->first();
            
        $lastVisit = $this->record->appointments()
            ->where('status', 'completed')
            ->orderBy('starts_at', 'desc')
            ->first();
        
        return [
            Stat::make('Termine gesamt', $appointmentStats->total)
                ->description($appointmentStats->completed . ' abgeschlossen')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success')
                ->chart([7, 12, 10, 15, 20, 18, 25]),
                
            Stat::make('Umsatz gesamt', '€' . number_format($revenue, 2, ',', '.'))
                ->description('Ø €' . number_format($avgAppointmentValue, 2, ',', '.') . ' pro Termin')
                ->descriptionIcon('heroicon-m-currency-euro')
                ->color('warning'),
                
            Stat::make('Absagen/No-Shows', $appointmentStats->cancelled + $appointmentStats->no_shows)
                ->description($appointmentStats->cancelled . ' storniert, ' . $appointmentStats->no_shows . ' nicht erschienen')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color($appointmentStats->no_shows > 2 ? 'danger' : 'gray'),
                
            Stat::make('Nächster Termin', $nextAppointment ? $nextAppointment->starts_at->format('d.m.Y H:i') : 'Keiner geplant')
                ->description($nextAppointment ? $nextAppointment->service?->name : 'Termin vereinbaren')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color($nextAppointment ? 'info' : 'gray'),
                
            Stat::make('Letzter Besuch', $lastVisit ? $lastVisit->starts_at->diffForHumans() : 'Noch nie')
                ->description($lastVisit ? $lastVisit->starts_at->format('d.m.Y') : 'Ersten Termin buchen')
                ->descriptionIcon('heroicon-m-clock')
                ->color('primary'),
                
            Stat::make('Kundenwert', $this->calculateCustomerValue())
                ->description($this->getCustomerValueDescription())
                ->descriptionIcon('heroicon-m-star')
                ->color($this->getCustomerValueColor()),
        ];
    }
    
    private function calculateCustomerValue(): string
    {
        $appointments = $this->record->appointments()->count();
        $revenue = $this->record->calculateTotalRevenue();
        $noShows = $this->record->appointments()->where('status', 'no_show')->count();
        
        $score = ($appointments * 10) + ($revenue / 100) - ($noShows * 20);
        
        if ($score >= 100) return '⭐⭐⭐⭐⭐';
        if ($score >= 75) return '⭐⭐⭐⭐';
        if ($score >= 50) return '⭐⭐⭐';
        if ($score >= 25) return '⭐⭐';
        return '⭐';
    }
    
    private function getCustomerValueDescription(): string
    {
        $appointments = $this->record->appointments()->count();
        
        if ($appointments >= 10) return 'VIP Stammkunde';
        if ($appointments >= 5) return 'Stammkunde';
        if ($appointments >= 2) return 'Wiederkehrender Kunde';
        if ($appointments >= 1) return 'Neukunde';
        return 'Potenzieller Kunde';
    }
    
    private function getCustomerValueColor(): string
    {
        $appointments = $this->record->appointments()->count();
        
        if ($appointments >= 10) return 'success';
        if ($appointments >= 5) return 'info';
        if ($appointments >= 2) return 'warning';
        return 'gray';
    }
}