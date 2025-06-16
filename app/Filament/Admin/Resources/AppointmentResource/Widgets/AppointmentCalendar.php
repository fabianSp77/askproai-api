<?php

namespace App\Filament\Admin\Resources\AppointmentResource\Widgets;

use Filament\Widgets\Widget;
use App\Models\Appointment;
use Illuminate\Database\Eloquent\Model;

class AppointmentCalendar extends Widget
{
    protected static string $view = 'filament.resources.appointment-resource.widgets.appointment-calendar';
    
    protected int | string | array $columnSpan = 'full';
    
    public function getEvents(): array
    {
        return Appointment::with(['customer', 'staff', 'service'])
            ->whereBetween('starts_at', [
                now()->startOfMonth()->subWeek(),
                now()->endOfMonth()->addWeek()
            ])
            ->get()
            ->map(function ($appointment) {
                $color = match($appointment->status) {
                    'pending' => '#f59e0b',
                    'confirmed' => '#3b82f6',
                    'completed' => '#10b981',
                    'cancelled' => '#ef4444',
                    'no_show' => '#6b7280',
                    default => '#6b7280'
                };
                
                return [
                    'id' => $appointment->id,
                    'title' => ($appointment->customer?->name ?? 'Kunde') . ' - ' . ($appointment->service?->name ?? 'Leistung'),
                    'start' => $appointment->starts_at?->toIso8601String(),
                    'end' => $appointment->ends_at?->toIso8601String() ?? $appointment->starts_at?->addMinutes($appointment->service?->duration ?? 60)->toIso8601String(),
                    'color' => $color,
                    'url' => route('filament.admin.resources.appointments.view', $appointment),
                    'extendedProps' => [
                        'status' => $appointment->status,
                        'staff' => $appointment->staff?->name,
                        'customer' => $appointment->customer?->name,
                        'service' => $appointment->service?->name,
                        'price' => $appointment->service?->price ? number_format($appointment->service->price / 100, 2, ',', '.') . ' €' : null,
                    ]
                ];
            })
            ->toArray();
    }
}