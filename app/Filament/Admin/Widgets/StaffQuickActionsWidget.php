<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\Widget;

class StaffQuickActionsWidget extends Widget
{
    protected static string $view = 'filament.admin.widgets.staff-quick-actions-widget';
    protected int | string | array $columnSpan = 1;
    protected static ?int $sort = 4;

    protected function getViewData(): array
    {
        return [
            'actions' => $this->getQuickActions(),
        ];
    }

    protected function getQuickActions(): array
    {
        return [
            [
                'label' => 'Termin blockieren',
                'icon' => 'heroicon-o-no-symbol',
                'color' => 'warning',
                'url' => '/admin/appointments/create?type=blocked',
                'description' => 'Zeit für Pause oder Privat blockieren',
            ],
            [
                'label' => 'Verfügbarkeit anzeigen',
                'icon' => 'heroicon-o-calendar',
                'color' => 'info',
                'url' => '/admin/staff/' . (auth()->user()->staff?->id ?? 1) . '/edit',
                'description' => 'Arbeitszeiten bearbeiten',
            ],
            [
                'label' => 'Kundenliste',
                'icon' => 'heroicon-o-users',
                'color' => 'success',
                'url' => '/admin/customers',
                'description' => 'Alle Kunden anzeigen',
            ],
            [
                'label' => 'Hilfe & Support',
                'icon' => 'heroicon-o-question-mark-circle',
                'color' => 'gray',
                'url' => '#',
                'description' => 'Anleitungen und Kontakt',
            ],
        ];
    }
}