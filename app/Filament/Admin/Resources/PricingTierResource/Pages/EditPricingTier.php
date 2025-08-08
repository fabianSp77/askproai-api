<?php

namespace App\Filament\Admin\Resources\PricingTierResource\Pages;

use App\Filament\Admin\Resources\PricingTierResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPricingTier extends EditRecord
{
    protected static string $resource = PricingTierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn () => auth()->user()->hasRole('reseller_owner')),
        ];
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}