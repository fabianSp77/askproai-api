<?php

namespace App\Filament\Resources\BalanceTopupResource\Pages;

use App\Filament\Resources\BalanceTopupResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewBalanceTopup extends ViewRecord
{
    protected static string $resource = BalanceTopupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
