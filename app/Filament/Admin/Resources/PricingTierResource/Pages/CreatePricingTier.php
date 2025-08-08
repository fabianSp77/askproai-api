<?php

namespace App\Filament\Admin\Resources\PricingTierResource\Pages;

use App\Filament\Admin\Resources\PricingTierResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePricingTier extends CreateRecord
{
    protected static string $resource = PricingTierResource::class;
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = auth()->user()->company_id;
        
        // If no overage rate specified, use sell price
        if (empty($data['overage_rate'])) {
            $data['overage_rate'] = $data['sell_price'];
        }
        
        return $data;
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}