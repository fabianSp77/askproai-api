<?php

namespace App\Filament\Resources\CurrencyExchangeRateResource\Pages;

use App\Filament\Resources\CurrencyExchangeRateResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewCurrencyExchangeRate extends ViewRecord
{
    protected static string $resource = CurrencyExchangeRateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    public function getTitle(): string
    {
        return sprintf(
            'Wechselkurs: 1 %s = %.6f %s',
            $this->record->from_currency,
            $this->record->rate,
            $this->record->to_currency
        );
    }
}