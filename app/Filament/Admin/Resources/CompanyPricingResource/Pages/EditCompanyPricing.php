<?php

namespace App\Filament\Admin\Resources\CompanyPricingResource\Pages;

use App\Filament\Admin\Resources\CompanyPricingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCompanyPricing extends EditRecord
{
    protected static string $resource = CompanyPricingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}