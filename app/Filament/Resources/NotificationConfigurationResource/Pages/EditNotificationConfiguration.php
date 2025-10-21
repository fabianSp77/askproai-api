<?php

namespace App\Filament\Resources\NotificationConfigurationResource\Pages;

use App\Filament\Resources\NotificationConfigurationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditNotificationConfiguration extends EditRecord
{
    protected static string $resource = NotificationConfigurationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): ?string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
