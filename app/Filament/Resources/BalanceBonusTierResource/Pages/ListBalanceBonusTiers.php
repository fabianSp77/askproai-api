<?php

namespace App\Filament\Resources\BalanceBonusTierResource\Pages;

use App\Filament\Resources\BalanceBonusTierResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBalanceBonusTiers extends ListRecords
{
    protected static string $resource = BalanceBonusTierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
