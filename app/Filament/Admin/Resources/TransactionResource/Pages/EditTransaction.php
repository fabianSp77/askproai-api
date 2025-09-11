<?php

namespace App\Filament\Admin\Resources\TransactionResource\Pages;

use App\Filament\Admin\Resources\TransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTransaction extends EditRecord
{
    protected static string $resource = TransactionResource::class;
    
    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
    
    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Format the data for display
        if (isset($data['amount_cents'])) {
            $data['amount_display'] = number_format($data['amount_cents'] / 100, 2) . ' €';
        }
        if (isset($data['balance_before_cents'])) {
            $data['balance_before_display'] = number_format($data['balance_before_cents'] / 100, 2) . ' €';
        }
        if (isset($data['balance_after_cents'])) {
            $data['balance_after_display'] = number_format($data['balance_after_cents'] / 100, 2) . ' €';
        }
        
        return $data;
    }
    
    protected function getSavedNotificationTitle(): ?string
    {
        return 'Transaktion aktualisiert';
    }
}