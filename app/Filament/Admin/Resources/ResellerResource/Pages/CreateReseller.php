<?php

namespace App\Filament\Admin\Resources\ResellerResource\Pages;

use App\Filament\Admin\Resources\ResellerResource;
use Filament\Resources\Pages\CreateRecord;

class CreateReseller extends CreateRecord
{
    protected static string $resource = ResellerResource::class;

    public function getTitle(): string
    {
        return 'Create New Reseller';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Ensure company_type is set to reseller
        $data['company_type'] = 'reseller';
        
        // Set default values if not provided
        $data['is_active'] = $data['is_active'] ?? true;
        $data['currency'] = $data['currency'] ?? 'EUR';
        $data['timezone'] = $data['timezone'] ?? 'Europe/Berlin';
        
        // Generate slug from name
        if (empty($data['slug']) && !empty($data['name'])) {
            $data['slug'] = \Str::slug($data['name']);
        }

        return $data;
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Reseller created successfully';
    }
}