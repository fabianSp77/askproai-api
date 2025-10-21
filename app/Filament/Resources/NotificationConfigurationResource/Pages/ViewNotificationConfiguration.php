<?php

namespace App\Filament\Resources\NotificationConfigurationResource\Pages;

use App\Filament\Resources\NotificationConfigurationResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewNotificationConfiguration extends ViewRecord
{
    protected static string $resource = NotificationConfigurationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
