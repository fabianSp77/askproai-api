<?php

namespace App\Filament\Resources\CompanyGatewayConfigurationResource\Pages;

use App\Filament\Resources\CompanyGatewayConfigurationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditCompanyGatewayConfiguration extends EditRecord
{
    protected static string $resource = CompanyGatewayConfigurationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->label('Löschen'),
        ];
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Gateway-Konfiguration gespeichert')
            ->body('Die Änderungen wurden erfolgreich übernommen. Cache wurde automatisch invalidiert.');
    }
}
