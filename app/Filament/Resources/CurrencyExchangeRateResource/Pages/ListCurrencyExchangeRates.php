<?php

namespace App\Filament\Resources\CurrencyExchangeRateResource\Pages;

use App\Filament\Resources\CurrencyExchangeRateResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCurrencyExchangeRates extends ListRecords
{
    protected static string $resource = CurrencyExchangeRateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Neuer Wechselkurs'),
        ];
    }

    public function getTitle(): string
    {
        return 'Wechselkurse verwalten';
    }
}