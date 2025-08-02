<?php

namespace App\Filament\Admin\Resources\BranchResource\Pages;

use App\Filament\Admin\Resources\BranchResource;
use App\Filament\Admin\Widgets\BranchPerformanceWidget;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBranches extends ListRecords
{
    protected static string $resource = BranchResource::class;

    public function mount(): void
    {
        // Emergency Company Context Fix
        if (auth()->check() && auth()->user()->company_id) {
            app()->instance('current_company_id', auth()->user()->company_id);
            app()->instance('company_context_source', 'web_auth');
        }
        parent::mount();
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Neue Filiale')
                ->icon('heroicon-o-plus-circle'),
            Actions\Action::make('map_view')
                ->label('Kartenansicht')
                ->icon('heroicon-o-map')
                ->color('info')
                ->action(function () {
                    \Filament\Notifications\Notification::make()
                        ->title('Kartenansicht')
                        ->body('Diese Funktion wird in einer zukünftigen Version verfügbar sein.')
                        ->info()
                        ->send();
                }),
            Actions\Action::make('import')
                ->label('Importieren')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('warning')
                ->action(function () {
                    \Filament\Notifications\Notification::make()
                        ->title('Import')
                        ->body('Import-Funktion wird vorbereitet...')
                        ->success()
                        ->send();
                }),
        ];
    }
    
    protected function getHeaderWidgets(): array
    {
        return [
            BranchPerformanceWidget::class,
        ];
    }
    
    public function getTitle(): string
    {
        return 'Filial-Management';
    }
    
    public function getSubheading(): ?string
    {
        return 'Verwalten Sie alle Standorte und deren Performance-Metriken';
    }
}
