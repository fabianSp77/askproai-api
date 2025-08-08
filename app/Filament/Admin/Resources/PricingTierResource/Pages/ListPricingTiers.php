<?php

namespace App\Filament\Admin\Resources\PricingTierResource\Pages;

use App\Filament\Admin\Resources\PricingTierResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPricingTiers extends ListRecords
{
    protected static string $resource = PricingTierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Neues Preismodell'),
        ];
    }
    
    protected function getHeaderWidgets(): array
    {
        return [
            PricingTierResource\Widgets\PricingOverview::class,
        ];
    }
}