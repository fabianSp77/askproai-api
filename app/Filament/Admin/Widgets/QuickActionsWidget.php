<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\Widget;

class QuickActionsWidget extends Widget
{
    protected static string $view = 'filament.admin.widgets.quick-actions-widget';
    protected int | string | array $columnSpan = 'full';
    protected static ?int $sort = -3;

    public function getQuickActions(): array
    {
        try {
            return [
                [
                    'label' => 'Neuer Termin',
                    'icon' => 'heroicon-o-calendar-days',
                    'url' => '/admin/appointments/create',
                    'color' => 'primary',
                    'description' => 'Termin manuell anlegen'
                ],
                [
                    'label' => 'Neuer Kunde',
                    'icon' => 'heroicon-o-user-plus',
                    'url' => '/admin/customers/create',
                    'color' => 'success',
                    'description' => 'Kunde erfassen'
                ],
                [
                    'label' => 'Mitarbeiter',
                    'icon' => 'heroicon-o-user-group',
                    'url' => '/admin/staff',
                    'color' => 'info',
                    'description' => 'Personal verwalten'
                ],
                [
                    'label' => 'Anrufe',
                    'icon' => 'heroicon-o-phone',
                    'url' => '/admin/calls',
                    'color' => 'warning',
                    'description' => 'Anrufprotokoll'
                ],
                [
                    'label' => 'System Status',
                    'icon' => 'heroicon-o-cpu-chip',
                    'url' => '/admin/system-monitoring',
                    'color' => 'gray',
                    'description' => 'System überwachen'
                ],
                [
                    'label' => 'Event Import',
                    'icon' => 'heroicon-o-arrow-down-tray',
                    'url' => '/admin/event-type-import-wizard',
                    'color' => 'purple',
                    'description' => 'Events importieren'
                ],
            ];
        } catch (\Exception $e) {
            // Fallback bei Fehlern
            return [];
        }
    }
}