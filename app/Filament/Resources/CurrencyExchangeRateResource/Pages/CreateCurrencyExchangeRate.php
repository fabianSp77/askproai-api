<?php

namespace App\Filament\Resources\CurrencyExchangeRateResource\Pages;

use App\Filament\Resources\CurrencyExchangeRateResource;
use Filament\Resources\Pages\CreateRecord;
use App\Models\CurrencyExchangeRate;

class CreateCurrencyExchangeRate extends CreateRecord
{
    protected static string $resource = CurrencyExchangeRateResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    public function getTitle(): string
    {
        return 'Neuer Wechselkurs';
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Wechselkurs erfolgreich erstellt';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Deactivate old rates when creating a new one
        if ($data['is_active'] ?? false) {
            CurrencyExchangeRate::deactivateOldRates($data['from_currency'], $data['to_currency']);
        }

        return $data;
    }
}