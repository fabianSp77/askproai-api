<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\Widget;
use App\Models\Appointment;
use Carbon\Carbon;

class AppointmentCalendar extends Widget
{
    protected static string $view = 'filament.admin.widgets.appointment-calendar';
    protected static ?int $sort = 2;
    
    public function getAppointments(): array
    {
        $start = Carbon::now()->startOfWeek();
        $end = Carbon::now()->endOfWeek();
        
        $appointments = Appointment::whereBetween('starts_at', [$start, $end])
            ->with(['customer', 'staff', 'service'])
            ->orderBy('starts_at')
            ->get();
            
        $events = [];
        
        foreach ($appointments as $appointment) {
            $events[] = [
                'id' => $appointment->id,
                'title' => ($appointment->customer?->name ?? 'Unbekannt') . ' - ' . ($appointment->service?->name ?? 'Keine Leistung'),
                'start' => $appointment->starts_at->toIso8601String(),
                'end' => $appointment->ends_at?->toIso8601String() ?? $appointment->starts_at->addMinutes(60)->toIso8601String(),
                'color' => $this->getStatusColor($appointment->status),
                'extendedProps' => [
                    'customer' => $appointment->customer?->name,
                    'phone' => $appointment->customer?->phone,
                    'staff' => $appointment->staff?->name,
                    'service' => $appointment->service?->name,
                    'price' => $appointment->service?->price ? number_format($appointment->service->price / 100, 2, ',', '.') . ' €' : null,
                    'status' => $appointment->status,
                    'statusLabel' => $this->getStatusLabel($appointment->status),
                ]
            ];
        }
        
        return $events;
    }
    
    protected function getStatusColor(string $status): string
    {
        return match ($status) {
            'pending' => '#f59e0b',
            'confirmed' => '#3b82f6',
            'completed' => '#10b981',
            'cancelled' => '#ef4444',
            'no_show' => '#6b7280',
            default => '#6b7280',
        };
    }
    
    protected function getStatusLabel(string $status): string
    {
        return match ($status) {
            'pending' => 'Ausstehend',
            'confirmed' => 'Bestätigt',
            'completed' => 'Abgeschlossen',
            'cancelled' => 'Abgesagt',
            'no_show' => 'Nicht erschienen',
            default => $status,
        };
    }
}