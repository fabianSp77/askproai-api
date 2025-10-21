<?php

namespace App\Filament\Resources\BalanceBonusTierResource\Pages;

use App\Filament\Resources\BalanceBonusTierResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBalanceBonusTier extends EditRecord
{
    protected static string $resource = BalanceBonusTierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
