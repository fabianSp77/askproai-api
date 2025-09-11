<?php

namespace App\Filament\Admin\Resources\BalanceTopupResource\Pages;

use App\Filament\Admin\Resources\BalanceTopupResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditBalanceTopup extends EditRecord
{
    protected static string $resource = BalanceTopupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make()
                ->requiresConfirmation()
                ->before(function () {
                    if ($this->record->status === 'succeeded') {
                        Notification::make()
                            ->title('Löschen nicht möglich')
                            ->body('Erfolgreiche Aufladungen können nicht gelöscht werden')
                            ->danger()
                            ->send();
                        
                        return false;
                    }
                }),
        ];
    }
    
    protected function getSavedNotificationTitle(): ?string
    {
        return 'Aufladung aktualisiert';
    }
    
    protected function beforeSave(): void
    {
        // Prevent editing of succeeded topups
        if ($this->record->status === 'succeeded') {
            Notification::make()
                ->title('Bearbeitung nicht möglich')
                ->body('Erfolgreiche Aufladungen können nicht bearbeitet werden')
                ->danger()
                ->send();
            
            $this->halt();
        }
    }
    
    protected function afterSave(): void
    {
        // If status changed to succeeded, credit the tenant
        if ($this->record->wasChanged('status') && $this->record->status === 'succeeded') {
            $this->record->markAsSucceeded();
            
            Notification::make()
                ->title('Guthaben gutgeschrieben')
                ->body("Das Guthaben wurde dem Tenant erfolgreich gutgeschrieben")
                ->success()
                ->send();
        }
        
        // Log the update
        activity()
            ->performedOn($this->record)
            ->causedBy(auth()->user())
            ->log("Aufladung #{$this->record->id} aktualisiert");
    }
}