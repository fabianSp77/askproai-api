<?php

namespace App\Filament\Admin\Resources\CompanyPricingResource\Pages;

use App\Filament\Admin\Resources\CompanyPricingResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCompanyPricing extends CreateRecord
{
    protected static string $resource = CompanyPricingResource::class;
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}