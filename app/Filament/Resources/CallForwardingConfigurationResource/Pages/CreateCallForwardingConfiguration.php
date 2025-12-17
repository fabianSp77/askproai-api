<?php

namespace App\Filament\Resources\CallForwardingConfigurationResource\Pages;

use App\Filament\Resources\CallForwardingConfigurationResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateCallForwardingConfiguration extends CreateRecord
{
    protected static string $resource = CallForwardingConfigurationResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Anrufweiterleitung erfolgreich erstellt';
    }
}
