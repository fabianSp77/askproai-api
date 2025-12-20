<?php

namespace App\Filament\Resources\ServiceCaseResource\Pages;

use App\Filament\Resources\ServiceCaseResource;
use Filament\Resources\Pages\CreateRecord;

class CreateServiceCase extends CreateRecord
{
    protected static string $resource = ServiceCaseResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Set default values if not provided
        $data['status'] = $data['status'] ?? 'new';
        $data['priority'] = $data['priority'] ?? 'normal';
        $data['urgency'] = $data['urgency'] ?? 'normal';
        $data['impact'] = $data['impact'] ?? 'normal';
        $data['output_status'] = $data['output_status'] ?? 'pending';

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
