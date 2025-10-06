<?php

namespace App\Filament\Resources\CurrencyExchangeRateResource\Pages;

use App\Filament\Resources\CurrencyExchangeRateResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Models\CurrencyExchangeRate;

class EditCurrencyExchangeRate extends EditRecord
{
    protected static string $resource = CurrencyExchangeRateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    public function getTitle(): string
    {
        return 'Wechselkurs bearbeiten';
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Wechselkurs erfolgreich aktualisiert';
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Deactivate old rates when updating to active
        if (($data['is_active'] ?? false) && !$this->record->is_active) {
            CurrencyExchangeRate::where('id', '!=', $this->record->id)
                ->where('from_currency', $data['from_currency'])
                ->where('to_currency', $data['to_currency'])
                ->update(['is_active' => false]);
        }

        return $data;
    }
}