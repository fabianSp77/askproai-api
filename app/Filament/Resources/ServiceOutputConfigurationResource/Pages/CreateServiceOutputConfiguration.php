<?php

namespace App\Filament\Resources\ServiceOutputConfigurationResource\Pages;

use App\Filament\Resources\ServiceOutputConfigurationResource;
use Filament\Resources\Pages\CreateRecord;

class CreateServiceOutputConfiguration extends CreateRecord
{
    protected static string $resource = ServiceOutputConfigurationResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Set default values if not provided
        $data['is_active'] = $data['is_active'] ?? true;
        $data['retry_on_failure'] = $data['retry_on_failure'] ?? true;

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
