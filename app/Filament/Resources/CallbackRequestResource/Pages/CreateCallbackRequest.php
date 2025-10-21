<?php

namespace App\Filament\Resources\CallbackRequestResource\Pages;

use App\Filament\Resources\CallbackRequestResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCallbackRequest extends CreateRecord
{
    protected static string $resource = CallbackRequestResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Rückrufanfrage erfolgreich erstellt';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Set default expires_at if not provided (24 hours from now)
        if (empty($data['expires_at'])) {
            $data['expires_at'] = now()->addHours(24);
        }

        return $data;
    }
}
