<?php

namespace App\Filament\Admin\Resources\PricingPlanResource\Pages;

use App\Filament\Admin\Resources\PricingPlanResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPricingPlan extends EditRecord
{
    protected static string $resource = PricingPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}