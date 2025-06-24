<?php

namespace App\Filament\Admin\Resources\AppointmentResource\Widgets;

use App\Models\Appointment;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;

class AppointmentCalendar extends Widget
{
    protected static string $view = 'filament.admin.resources.appointment-resource.widgets.appointment-calendar';
    
    protected static ?int $sort = 3;
    
    public function getAppointments(): array
    {
        $company = auth()->user()->company;
        
        if (!$company) {
            return [];
        }
        
        $appointments = Appointment::where('company_id', $company->id)
            ->where('starts_at', '>=', now()->startOfMonth())
            ->where('starts_at', '<=', now()->endOfMonth())
            ->with(['customer', 'service', 'staff'])
            ->get();
            
        return $appointments->map(function ($appointment) {
            return [
                'id' => $appointment->id,
                'title' => $appointment->customer?->name ?? 'Unbekannt',
                'start' => $appointment->starts_at->toIso8601String(),
                'end' => $appointment->ends_at?->toIso8601String() ?? $appointment->starts_at->addMinutes($appointment->service?->duration ?? 60)->toIso8601String(),
                'color' => match($appointment->status) {
                    'pending' => '#f59e0b',
                    'confirmed' => '#3b82f6',
                    'completed' => '#10b981',
                    'cancelled' => '#ef4444',
                    'no_show' => '#6b7280',
                    default => '#6b7280'
                },
                'extendedProps' => [
                    'service' => $appointment->service?->name,
                    'staff' => $appointment->staff?->name,
                    'status' => $appointment->status,
                    'phone' => $appointment->customer?->phone,
                ]
            ];
        })->toArray();
    }
}