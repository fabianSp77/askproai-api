<?php

namespace App\Filament\Resources\ServiceCaseCategoryResource\Pages;

use App\Filament\Resources\ServiceCaseCategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateServiceCaseCategory extends CreateRecord
{
    protected static string $resource = ServiceCaseCategoryResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Set default values if not provided
        $data['is_active'] = $data['is_active'] ?? true;
        $data['sort_order'] = $data['sort_order'] ?? 0;
        $data['confidence_threshold'] = $data['confidence_threshold'] ?? 0.5;

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
