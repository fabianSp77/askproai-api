<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\Widget;
use App\Models\Appointment;
use App\Models\Staff;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class NextAppointmentWidget extends Widget
{
    protected static string $view = 'filament.admin.widgets.next-appointment-widget';
    protected int | string | array $columnSpan = 1;
    protected static ?int $sort = 2;

    protected function getViewData(): array
    {
        $user = Auth::user();
        $staff = Staff::where('user_id', $user->id)->first();
        
        if (!$staff) {
            return [
                'nextAppointment' => null,
                'timeUntil' => null,
                'isNow' => false,
            ];
        }

        $now = Carbon::now();
        
        // Nächster Termin
        $nextAppointment = Appointment::with(['customer', 'service', 'branch'])
            ->where('staff_id', $staff->id)
            ->where('starts_at', '>', $now)
            ->whereIn('status', ['scheduled', 'confirmed'])
            ->orderBy('starts_at')
            ->first();

        // Prüfen ob ein Termin gerade läuft
        $currentAppointment = Appointment::with(['customer', 'service', 'branch'])
            ->where('staff_id', $staff->id)
            ->where('starts_at', '<=', $now)
            ->where('ends_at', '>', $now)
            ->whereIn('status', ['scheduled', 'confirmed'])
            ->first();

        if ($currentAppointment) {
            return [
                'nextAppointment' => $currentAppointment,
                'timeUntil' => null,
                'isNow' => true,
            ];
        }

        $timeUntil = null;
        if ($nextAppointment) {
            $diffInMinutes = $now->diffInMinutes($nextAppointment->starts_at);
            
            if ($diffInMinutes < 60) {
                $timeUntil = $diffInMinutes . ' Minuten';
            } elseif ($diffInMinutes < 1440) {
                $hours = floor($diffInMinutes / 60);
                $minutes = $diffInMinutes % 60;
                $timeUntil = $hours . ' Std ' . ($minutes > 0 ? $minutes . ' Min' : '');
            } else {
                $timeUntil = $nextAppointment->starts_at->diffForHumans();
            }
        }

        return [
            'nextAppointment' => $nextAppointment,
            'timeUntil' => $timeUntil,
            'isNow' => false,
        ];
    }

    public function getColumnSpan(): int | string | array
    {
        return [
            'default' => 1,
            'sm' => 1,
            'md' => 1,
            'lg' => 1,
            'xl' => 1,
        ];
    }
}