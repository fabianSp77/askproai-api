<?php

namespace App\Filament\Customer\Resources\TransactionResource\Pages;

use App\Filament\Customer\Resources\TransactionResource;
use Filament\Resources\Pages\ListRecords;

class ListTransactions extends ListRecords
{
    protected static string $resource = TransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
