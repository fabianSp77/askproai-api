<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Contracts\Support\Htmlable;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static ?string $title = 'Dashboard';

    protected static ?string $navigationLabel = 'Dashboard';

    public function getHeading(): string|Htmlable
    {
        $user = auth()->user();
        $greeting = $this->getGreeting();
        $name = $user->name ?? 'User';

        return "{$greeting}, {$name}! 👋";
    }

    public function getSubheading(): string|Htmlable|null
    {
        $date = now()->locale('de')->isoFormat('dddd, D. MMMM YYYY');
        return "Heute ist {$date}";
    }

    protected function getGreeting(): string
    {
        $hour = now()->hour;

        if ($hour < 6) {
            return 'Gute Nacht';
        } elseif ($hour < 11) {
            return 'Guten Morgen';
        } elseif ($hour < 14) {
            return 'Guten Tag';
        } elseif ($hour < 18) {
            return 'Guten Nachmittag';
        } else {
            return 'Guten Abend';
        }
    }

    public function getColumns(): int | string | array
    {
        return [
            'sm' => 1,
            'md' => 2,
            'lg' => 3,
            'xl' => 4,
            '2xl' => 5,
        ];
    }

    public function getWidgets(): array
    {
        return [
            // 🆕 PHASE 3 (2025-11-24): Appointment Sync Monitoring
            \App\Filament\Widgets\AppointmentSyncStatusWidget::class,

            // 📊 Cal.com Integration Status
            \App\Filament\Widgets\CalcomSyncStatusWidget::class,
        ];
    }

    protected function getViewData(): array
    {
        return [
            'hasFiltersLayout' => false,
        ];
    }
}