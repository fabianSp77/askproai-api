<?php

namespace App\Filament\Customer\Resources\BalanceTopupResource\Pages;

use App\Filament\Customer\Resources\BalanceTopupResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewBalanceTopup extends ViewRecord
{
    protected static string $resource = BalanceTopupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No edit or delete actions - READ-ONLY
        ];
    }
}
