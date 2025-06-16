<?php

namespace App\Filament\Admin\Resources\StaffResource\Pages;

use App\Filament\Admin\Resources\StaffResource;
use App\Filament\Admin\Widgets\StaffProductivityWidget;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStaff extends ListRecords
{
    protected static string $resource = StaffResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Neuer Mitarbeiter')
                ->icon('heroicon-o-plus-circle'),
            Actions\Action::make('schedule_overview')
                ->label('Dienstplan-Übersicht')
                ->icon('heroicon-o-calendar')
                ->color('info')
                ->action(function () {
                    \Filament\Notifications\Notification::make()
                        ->title('Dienstplan-Übersicht')
                        ->body('Diese Funktion wird in einer zukünftigen Version verfügbar sein.')
                        ->info()
                        ->send();
                }),
            Actions\Action::make('skills_matrix')
                ->label('Kompetenz-Matrix')
                ->icon('heroicon-o-academic-cap')
                ->color('warning')
                ->action(function () {
                    \Filament\Notifications\Notification::make()
                        ->title('Kompetenz-Matrix')
                        ->body('Diese Funktion wird in einer zukünftigen Version verfügbar sein.')
                        ->info()
                        ->send();
                }),
            Actions\Action::make('export')
                ->label('Exportieren')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function () {
                    \Filament\Notifications\Notification::make()
                        ->title('Export')
                        ->body('Export wird vorbereitet...')
                        ->success()
                        ->send();
                }),
        ];
    }
    
    protected function getHeaderWidgets(): array
    {
        return [
            StaffProductivityWidget::class,
        ];
    }
    
    public function getTitle(): string
    {
        return 'Mitarbeiter-Management';
    }
    
    public function getSubheading(): ?string
    {
        return 'Verwalten Sie Ihr Team mit intelligenten Produktivitäts- und Performance-Analysen';
    }
}
