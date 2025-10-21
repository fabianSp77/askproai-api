<?php

namespace App\Filament\Resources\NotificationConfigurationResource\Pages;

use App\Filament\Resources\NotificationConfigurationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListNotificationConfigurations extends ListRecords
{
    protected static string $resource = NotificationConfigurationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return NotificationConfigurationResource::getWidgets();
    }
}
