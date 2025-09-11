<?php

namespace App\Filament\Admin\Resources\BalanceTopupResource\Pages;

use App\Filament\Admin\Resources\BalanceTopupResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateBalanceTopup extends CreateRecord
{
    protected static string $resource = BalanceTopupResource::class;
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
    
    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Aufladung erfolgreich erstellt';
    }
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Set initiated_by to current user
        $data['initiated_by'] = auth()->id();
        
        // Set default status if not provided
        if (!isset($data['status'])) {
            $data['status'] = 'pending';
        }
        
        // If manual payment method and succeeded status, set paid_at
        if ($data['payment_method'] === 'manual' && $data['status'] === 'succeeded') {
            $data['paid_at'] = now();
        }
        
        return $data;
    }
    
    protected function afterCreate(): void
    {
        // If status is succeeded, credit the tenant immediately
        if ($this->record->status === 'succeeded') {
            $this->record->markAsSucceeded();
            
            Notification::make()
                ->title('Guthaben gutgeschrieben')
                ->body("Das Guthaben wurde dem Tenant erfolgreich gutgeschrieben")
                ->success()
                ->send();
        }
        
        // Log the creation
        activity()
            ->performedOn($this->record)
            ->causedBy(auth()->user())
            ->log("Aufladung #{$this->record->id} erstellt: {$this->record->getFormattedTotal()}");
    }
}