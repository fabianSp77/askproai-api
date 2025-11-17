<?php

namespace App\Filament\Resources\CallForwardingConfigurationResource\Pages;

use App\Filament\Resources\CallForwardingConfigurationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCallForwardingConfiguration extends EditRecord
{
    protected static string $resource = CallForwardingConfigurationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->label('Ansehen')
                ->icon('heroicon-o-eye'),
            Actions\DeleteAction::make()
                ->label('Löschen')
                ->icon('heroicon-o-trash')
                ->requiresConfirmation(),
            Actions\ForceDeleteAction::make()
                ->label('Endgültig löschen')
                ->icon('heroicon-o-trash')
                ->requiresConfirmation(),
            Actions\RestoreAction::make()
                ->label('Wiederherstellen')
                ->icon('heroicon-o-arrow-path'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Anrufweiterleitung erfolgreich aktualisiert';
    }
}
