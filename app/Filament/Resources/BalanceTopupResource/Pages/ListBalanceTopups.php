<?php

namespace App\Filament\Resources\BalanceTopupResource\Pages;

use App\Filament\Resources\BalanceTopupResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBalanceTopups extends ListRecords
{
    protected static string $resource = BalanceTopupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Resources\BalanceTopupResource\Widgets\BalanceTopupStats::class,
        ];
    }
}
