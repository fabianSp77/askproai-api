<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\Widget;
use App\Models\Appointment;
use App\Models\Staff;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class MyAppointmentsTodayWidget extends Widget
{
    protected static string $view = 'filament.admin.widgets.my-appointments-today-widget';
    protected int | string | array $columnSpan = 'full';
    protected static ?int $sort = 1;

    protected function getViewData(): array
    {
        $user = Auth::user();
        $staff = Staff::where('user_id', $user->id)->first();
        
        if (!$staff) {
            return [
                'appointments' => collect(),
                'totalCount' => 0,
                'completedCount' => 0,
                'upcomingCount' => 0,
                'currentAppointment' => null,
            ];
        }

        $appointments = Appointment::with(['customer', 'service', 'branch'])
            ->where('staff_id', $staff->id)
            ->whereDate('starts_at', today())
            ->orderBy('starts_at')
            ->get();

        $now = Carbon::now();
        $completedCount = $appointments->where('status', 'completed')->count();
        $upcomingCount = $appointments->where('starts_at', '>', $now)->count();
        
        // Aktueller oder nächster Termin
        $currentAppointment = $appointments
            ->where('starts_at', '<=', $now->addMinutes(30))
            ->where('ends_at', '>', $now)
            ->first();

        if (!$currentAppointment) {
            $currentAppointment = $appointments
                ->where('starts_at', '>', $now)
                ->first();
        }

        return [
            'appointments' => $appointments,
            'totalCount' => $appointments->count(),
            'completedCount' => $completedCount,
            'upcomingCount' => $upcomingCount,
            'currentAppointment' => $currentAppointment,
        ];
    }

    public function getColumnSpan(): int | string | array
    {
        return [
            'default' => 'full',
            'sm' => 'full',
            'md' => 'full',
            'lg' => 2,
            'xl' => 2,
        ];
    }
}