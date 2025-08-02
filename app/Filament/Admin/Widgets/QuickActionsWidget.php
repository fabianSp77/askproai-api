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
                    'label' => __('admin.quick_actions.new_appointment'),
                    'icon' => 'heroicon-o-calendar-days',
                    'url' => route('filament.admin.resources.appointments.create'),
                    'color' => 'primary',
                    'description' => 'Termin manuell anlegen'
                ],
                [
                    'label' => 'Neuer Kunde',
                    'icon' => 'heroicon-o-user-plus',
                    'url' => route('filament.admin.resources.customers.create'),
                    'color' => 'success',
                    'description' => 'Kunde erfassen'
                ],
                [
                    'label' => 'Mitarbeiter',
                    'icon' => 'heroicon-o-user-group',
                    'url' => route('filament.admin.resources.staff.index'),
                    'color' => 'info',
                    'description' => 'Personal verwalten'
                ],
                [
                    'label' => __('admin.quick_actions.calls'),
                    'icon' => 'heroicon-o-phone',
                    'url' => route('filament.admin.resources.calls.index'),
                    'color' => 'warning',
                    'description' => 'Anrufprotokoll'
                ],
                [
                    'label' => __('admin.quick_actions.system_status'),
                    'icon' => 'heroicon-o-cpu-chip',
                    'url' => route('filament.admin.pages.system-monitoring-dashboard'),
                    'color' => 'gray',
                    'description' => 'System überwachen'
                ],
                [
                    'label' => 'Event Import',
                    'icon' => 'heroicon-o-arrow-down-tray',
                    'url' => route('filament.admin.pages.event-type-import-wizard'),
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