<?php

namespace App\Filament\Resources\CompanyServicePricingResource\Pages;

use App\Filament\Resources\CompanyServicePricingResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCompanyServicePricing extends CreateRecord
{
    protected static string $resource = CompanyServicePricingResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();

        return $data;
    }
}
