<?php

namespace App\Filament\Admin\Resources\CompanyResource\Pages;

use App\Filament\Admin\Resources\CompanyResource;
use App\Filament\Admin\Widgets\CompanyDashboardWidget;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCompanies extends ListRecords
{
    protected static string $resource = CompanyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('quickSetup')
                ->label('🚀 Quick Setup (3 Min)')
                ->icon('heroicon-o-sparkles')
                ->color('success')
                ->url('/admin/quick-setup-wizard')
                ->size('lg'),
            Actions\CreateAction::make()
                ->label('Manuell anlegen')
                ->icon('heroicon-o-plus-circle')
                ->color('gray'),
            Actions\Action::make('sync_all')
                ->label('Alle synchronisieren')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->action(function () {
                    // Sync logic would go here
                    $this->notify('success', 'Synchronisation gestartet...');
                })
                ->requiresConfirmation()
                ->modalHeading('Alle Unternehmen synchronisieren')
                ->modalDescription('Dies wird alle Cal.com Event-Types und Retell.ai Agenten für alle Unternehmen synchronisieren. Dies kann einige Minuten dauern.'),
            Actions\Action::make('export')
                ->label('Exportieren')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function () {
                    // Export logic would go here
                    $this->notify('success', 'Export wird vorbereitet...');
                }),
        ];
    }
    
    protected function getHeaderWidgets(): array
    {
        return [
            // Temporarily disabled to debug loading issue
            // CompanyDashboardWidget::class,
        ];
    }
    
    public function getTitle(): string
    {
        return 'Unternehmens-Verwaltung';
    }
    
    public function getSubheading(): ?string
    {
        return 'Verwalten Sie alle Kundenunternehmen und deren Konfigurationen zentral';
    }
}
