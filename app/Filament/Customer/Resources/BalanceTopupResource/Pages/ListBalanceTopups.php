<?php

namespace App\Filament\Customer\Resources\BalanceTopupResource\Pages;

use App\Filament\Customer\Resources\BalanceTopupResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBalanceTopups extends ListRecords
{
    protected static string $resource = BalanceTopupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No create action - READ-ONLY
        ];
    }
}
