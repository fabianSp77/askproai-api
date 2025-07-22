<?php

namespace App\Filament\Admin\Resources\RetellAgentResource\Pages;

use App\Filament\Admin\Resources\RetellAgentResource;
use App\Services\AgentSelectionService;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Notifications\Notification;

class ListRetellAgents extends ListRecords
{
    protected static string $resource = RetellAgentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('importFromRetell')
                ->label('Import from Retell.ai')
                ->icon('heroicon-o-cloud-arrow-down')
                ->color('info')
                ->requiresConfirmation()
                ->modalHeading('Import Agents from Retell.ai')
                ->modalDescription('This will import all agents from your Retell.ai account.')
                ->action(function () {
                    $service = app(AgentSelectionService::class);
                    $company = auth()->user()->company;
                    
                    $importedAgents = $service->importAgentsFromRetell($company);
                    
                    Notification::make()
                        ->title('Import Complete')
                        ->body("Imported {$importedAgents->count()} agents from Retell.ai")
                        ->success()
                        ->send();
                }),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            RetellAgentResource\Widgets\AgentPerformanceStats::class,
        ];
    }
}