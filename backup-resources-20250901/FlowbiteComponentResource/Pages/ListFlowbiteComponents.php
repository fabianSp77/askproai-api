<?php

namespace App\Filament\Resources\FlowbiteComponentResource\Pages;

use App\Filament\Resources\FlowbiteComponentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFlowbiteComponents extends ListRecords
{
    protected static string $resource = FlowbiteComponentResource::class;
    
    protected static ?string $title = 'Flowbite Pro Components';
    
    protected ?string $subheading = 'Browse and integrate 556+ premium UI components';
    
    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('playground')
                ->label('Component Playground')
                ->icon('heroicon-o-play')
                ->url(fn () => static::getResource()::getUrl('playground'))
                ->color('success'),
                
            Actions\Action::make('documentation')
                ->label('Documentation')
                ->icon('heroicon-o-book-open')
                ->url('/flowbite/docs')
                ->openUrlInNewTab(),
                
            Actions\Action::make('refresh')
                ->label('Refresh Components')
                ->icon('heroicon-o-arrow-path')
                ->action(fn () => cache()->forget('flowbite.components'))
                ->requiresConfirmation()
                ->modalHeading('Refresh Component Library')
                ->modalDescription('This will rescan all Flowbite components and update the library.')
                ->modalSubmitActionLabel('Refresh')
                ->successNotificationTitle('Components refreshed successfully'),
        ];
    }
    
    protected function getHeaderWidgets(): array
    {
        return [
            // Widgets will be added later
        ];
    }
    
    // Remove this method - Filament will handle the data retrieval
}