<?php

namespace App\Filament\Admin\Resources\AppointmentResource\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Appointment;
use Carbon\Carbon;

class AppointmentTrendsWidget extends ChartWidget
{
    protected static ?string $heading = 'Termin-Trends';
    protected static ?string $description = 'Entwicklung der Termine über die letzten 30 Tage';
    
    protected function getData(): array
    {
        $days = 30;
        $appointments = [];
        $completed = [];
        $cancelled = [];
        $noShows = [];
        $labels = [];
        
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $labels[] = $date->format('d.m');
            
            $dayAppointments = Appointment::whereDate('starts_at', $date);
            
            $appointments[] = $dayAppointments->count();
            $completed[] = (clone $dayAppointments)->where('status', 'completed')->count();
            $cancelled[] = (clone $dayAppointments)->where('status', 'cancelled')->count();
            $noShows[] = (clone $dayAppointments)->where('status', 'no_show')->count();
        }
        
        return [
            'datasets' => [
                [
                    'label' => 'Gesamt',
                    'data' => $appointments,
                    'borderColor' => 'rgb(59, 130, 246)',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                ],
                [
                    'label' => 'Abgeschlossen',
                    'data' => $completed,
                    'borderColor' => 'rgb(34, 197, 94)',
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                ],
                [
                    'label' => 'Abgesagt',
                    'data' => $cancelled,
                    'borderColor' => 'rgb(239, 68, 68)',
                    'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                ],
                [
                    'label' => 'No-Shows',
                    'data' => $noShows,
                    'borderColor' => 'rgb(251, 146, 60)',
                    'backgroundColor' => 'rgba(251, 146, 60, 0.1)',
                ],
            ],
            'labels' => $labels,
        ];
    }
    
    protected function getType(): string
    {
        return 'line';
    }
    
    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'stepSize' => 1,
                    ],
                ],
            ],
            'maintainAspectRatio' => false,
        ];
    }
}