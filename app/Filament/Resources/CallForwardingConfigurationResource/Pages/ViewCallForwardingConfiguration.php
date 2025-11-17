<?php

namespace App\Filament\Resources\CallForwardingConfigurationResource\Pages;

use App\Filament\Resources\CallForwardingConfigurationResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewCallForwardingConfiguration extends ViewRecord
{
    protected static string $resource = CallForwardingConfigurationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Bearbeiten')
                ->icon('heroicon-o-pencil'),
            Actions\DeleteAction::make()
                ->label('Löschen')
                ->icon('heroicon-o-trash')
                ->requiresConfirmation(),
        ];
    }
}
