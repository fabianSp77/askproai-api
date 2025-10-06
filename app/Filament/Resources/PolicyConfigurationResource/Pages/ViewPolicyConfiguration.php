<?php

namespace App\Filament\Resources\PolicyConfigurationResource\Pages;

use App\Filament\Resources\PolicyConfigurationResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPolicyConfiguration extends ViewRecord
{
    protected static string $resource = PolicyConfigurationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Bearbeiten'),
            Actions\DeleteAction::make()
                ->label('Löschen')
                ->requiresConfirmation(),
            Actions\ForceDeleteAction::make()
                ->label('Endgültig löschen')
                ->requiresConfirmation(),
            Actions\RestoreAction::make()
                ->label('Wiederherstellen'),
        ];
    }
}
