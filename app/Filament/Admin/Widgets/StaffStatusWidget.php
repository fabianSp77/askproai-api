<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\Widget;
use App\Models\Staff;
use Illuminate\Support\Facades\Auth;

class StaffStatusWidget extends Widget
{
    protected static string $view = 'filament.admin.widgets.staff-status-widget';
    protected int | string | array $columnSpan = 1;
    protected static ?int $sort = 5;
    protected static ?string $pollingInterval = '30s';

    protected function getViewData(): array
    {
        $user = Auth::user();
        $staff = Staff::where('user_id', $user->id)->first();
        
        return [
            'status' => $this->getCurrentStatus($staff),
            'workingHours' => $this->getTodayWorkingHours($staff),
            'breakTime' => $this->getBreakTimeInfo($staff),
        ];
    }

    protected function getCurrentStatus($staff): array
    {
        if (!$staff) {
            return [
                'label' => 'Nicht angemeldet',
                'color' => 'gray',
                'icon' => 'heroicon-o-x-circle',
            ];
        }

        // TODO: Implement real status tracking
        $isWorking = now()->between(
            now()->setHour(8)->setMinute(0),
            now()->setHour(18)->setMinute(0)
        );

        if ($isWorking) {
            return [
                'label' => 'Im Dienst',
                'color' => 'success',
                'icon' => 'heroicon-o-check-circle',
            ];
        }

        return [
            'label' => 'Außer Dienst',
            'color' => 'warning',
            'icon' => 'heroicon-o-clock',
        ];
    }

    protected function getTodayWorkingHours($staff): ?array
    {
        if (!$staff) {
            return null;
        }

        // TODO: Get from actual working hours configuration
        return [
            'start' => '08:00',
            'end' => '18:00',
            'total' => '10 Stunden',
        ];
    }

    protected function getBreakTimeInfo($staff): array
    {
        // TODO: Implement real break tracking
        return [
            'next' => '12:30 - 13:00',
            'taken' => 0,
            'remaining' => 30,
        ];
    }
}