<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Appointment;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Model;

class AppointmentCalendarWidget extends Widget
{
    protected static string $view = 'filament.admin.widgets.appointment-calendar-widget';
    
    protected int | string | array $columnSpan = 'full';
    
    protected static ?int $sort = 2;
    
    protected static ?string $pollingInterval = '60s';
    
    public function getAppointments(): array
    {
        return Appointment::with(['customer', 'staff', 'service'])
            ->whereDate('starts_at', today())
            ->orderBy('starts_at')
            ->get()
            ->map(function ($appointment) {
                return [
                    'id' => $appointment->id,
                    'time' => $appointment->starts_at->format('H:i'),
                    'customer' => $appointment->customer->name,
                    'service' => $appointment->service->name,
                    'staff' => $appointment->staff->name,
                    'status' => $appointment->status,
                    'revenue' => $appointment->service->price,
                ];
            })
            ->toArray();
    }
    
    public function getStats(): array
    {
        $today = Appointment::whereDate('starts_at', today());
        $thisWeek = Appointment::whereBetween('starts_at', [now()->startOfWeek(), now()->endOfWeek()]);
        
        return [
            'todayCount' => $today->count(),
            'todayRevenue' => $today->with('service')->get()->sum(fn($a) => $a->service->price ?? 0),
            'weekCount' => $thisWeek->count(),
            'weekRevenue' => $thisWeek->with('service')->get()->sum(fn($a) => $a->service->price ?? 0),
            'nextAppointment' => Appointment::where('starts_at', '>', now())
                ->where('status', 'confirmed')
                ->orderBy('starts_at')
                ->first(),
        ];
    }
}