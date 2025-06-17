<?php

namespace App\Filament\Admin\Resources\CompanyPricingResource\Pages;

use App\Filament\Admin\Resources\CompanyPricingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCompanyPricings extends ListRecords
{
    protected static string $resource = CompanyPricingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
    
    protected function getHeaderWidgets(): array
    {
        return [
            // CompanyPricingResource\Widgets\PricingOverview::class,
        ];
    }
}