<?php

namespace App\Filament\Resources\PolicyConfigurationResource\Pages;

use App\Filament\Resources\PolicyConfigurationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPolicyConfiguration extends EditRecord
{
    protected static string $resource = PolicyConfigurationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->label('Ansehen'),
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

    protected function getRedirectUrl(): ?string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Richtlinienkonfiguration erfolgreich gespeichert';
    }
}
