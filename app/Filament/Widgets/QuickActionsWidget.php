<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\HtmlString;

class QuickActionsWidget extends Widget
{
    protected static string $view = 'filament.widgets.quick-actions';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Schnellaktionen';

    public function getActions(): array
    {
        try {
            return [
                [
                    'label' => 'Neuer Kunde',
                    'icon' => 'heroicon-o-user-plus',
                    'color' => 'success',
                    'color_class' => 'bg-success-500 hover:bg-success-600',
                    'url' => route('filament.admin.resources.customers.create'),
                    'description' => 'Kunde erfassen',
                    'badge' => null,
                ],
                [
                    'label' => 'Neuer Termin',
                    'icon' => 'heroicon-o-calendar-days',
                    'color' => 'primary',
                    'color_class' => 'bg-primary-500 hover:bg-primary-600',
                    'url' => route('filament.admin.resources.appointments.create'),
                    'description' => 'Termin planen',
                    'badge' => $this->getUpcomingAppointmentCount(),
                ],
                [
                    'label' => 'Anruf erfassen',
                    'icon' => 'heroicon-o-phone',
                    'color' => 'info',
                    'color_class' => 'bg-info-500 hover:bg-info-600',
                    'url' => route('filament.admin.resources.calls.create'),
                    'description' => 'Gespräch dokumentieren',
                    'badge' => $this->getTodayCallCount(),
                ],
                [
                    'label' => 'Neue Rechnung',
                    'icon' => 'heroicon-o-document-text',
                    'color' => 'warning',
                    'color_class' => 'bg-warning-500 hover:bg-warning-600',
                    'url' => route('filament.admin.resources.invoices.create'),
                    'description' => 'Rechnung erstellen',
                    'badge' => $this->getOpenInvoiceCount(),
                ],
                [
                    'label' => 'Service anlegen',
                    'icon' => 'heroicon-o-briefcase',
                    'color' => 'purple',
                    'color_class' => 'bg-purple-500 hover:bg-purple-600',
                    'url' => route('filament.admin.resources.services.create'),
                    'description' => 'Neuen Service',
                    'badge' => null,
                ],
                [
                    'label' => 'Berichte',
                    'icon' => 'heroicon-o-chart-bar',
                    'color' => 'gray',
                    'color_class' => 'bg-gray-500 hover:bg-gray-600',
                    'url' => '#',
                    'description' => 'Analytics anzeigen',
                    'badge' => null,
                ],
            ];
        } catch (\Exception $e) {
            \Log::error('QuickActionsWidget::getActions Error: ' . $e->getMessage());
            return [];
        }
    }

    protected function getUpcomingAppointmentCount(): ?string
    {
        try {
            $count = \App\Models\Appointment::where('starts_at', '>=', now())
                ->where('status', 'scheduled')
                ->count();
            return $count > 0 ? (string)$count : null;
        } catch (\Exception $e) {
            \Log::error('QuickActionsWidget::getUpcomingAppointmentCount Error: ' . $e->getMessage());
            return null;
        }
    }

    protected function getTodayCallCount(): ?string
    {
        try {
            $count = \App\Models\Call::whereDate('created_at', today())->count();
            return $count > 0 ? (string)$count : null;
        } catch (\Exception $e) {
            \Log::error('QuickActionsWidget::getTodayCallCount Error: ' . $e->getMessage());
            return null;
        }
    }

    protected function getOpenInvoiceCount(): ?string
    {
        try {
            $count = \App\Models\Invoice::whereIn('status', ['pending', 'overdue'])->count();
            return $count > 0 ? (string)$count : null;
        } catch (\Exception $e) {
            \Log::error('QuickActionsWidget::getOpenInvoiceCount Error: ' . $e->getMessage());
            return null;
        }
    }

    protected function getViewData(): array
    {
        try {
            return [
                'actions' => $this->getActions(),
                'heading' => static::$heading,
            ];
        } catch (\Exception $e) {
            \Log::error('QuickActionsWidget::getViewData Error: ' . $e->getMessage());
            return [
                'actions' => [],
                'heading' => static::$heading,
            ];
        }
    }
}