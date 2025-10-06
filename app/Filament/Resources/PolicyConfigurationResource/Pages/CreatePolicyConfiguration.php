<?php

namespace App\Filament\Resources\PolicyConfigurationResource\Pages;

use App\Filament\Resources\PolicyConfigurationResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePolicyConfiguration extends CreateRecord
{
    protected static string $resource = PolicyConfigurationResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Richtlinienkonfiguration erfolgreich erstellt';
    }
}
