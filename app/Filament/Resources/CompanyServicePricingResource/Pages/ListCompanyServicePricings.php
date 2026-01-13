<?php

namespace App\Filament\Resources\CompanyServicePricingResource\Pages;

use App\Filament\Resources\CompanyServicePricingResource;
use App\Models\Company;
use App\Models\CompanyServicePricing;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListCompanyServicePricings extends ListRecords
{
    protected static string $resource = CompanyServicePricingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Neuer Kundenpreis'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            CompanyServicePricingResource\Widgets\PricingStatsOverview::class,
        ];
    }
}
