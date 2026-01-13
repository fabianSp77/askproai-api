<?php

namespace App\Filament\Resources\CompanyGatewayConfigurationResource\Pages;

use App\Filament\Resources\CompanyGatewayConfigurationResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCompanyGatewayConfiguration extends CreateRecord
{
    protected static string $resource = CompanyGatewayConfigurationResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Ensure company_id is set from authenticated user if not provided
        if (empty($data['company_id'])) {
            $data['company_id'] = auth()->user()?->company_id;
        }

        return $data;
    }
}
