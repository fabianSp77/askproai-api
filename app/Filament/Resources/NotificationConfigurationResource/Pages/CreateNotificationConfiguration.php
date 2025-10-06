<?php

namespace App\Filament\Resources\NotificationConfigurationResource\Pages;

use App\Filament\Resources\NotificationConfigurationResource;
use Filament\Resources\Pages\CreateRecord;

class CreateNotificationConfiguration extends CreateRecord
{
    protected static string $resource = NotificationConfigurationResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
