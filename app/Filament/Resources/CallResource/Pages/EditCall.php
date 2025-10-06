<?php

namespace App\Filament\Resources\CallResource\Pages;

use App\Filament\Resources\CallResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditCall extends EditRecord
{
    protected static string $resource = CallResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    public function getTitle(): string
    {
        // Priority: customer_name field > linked customer > fallback
        if ($this->record->customer_name) {
            $customerName = $this->record->customer_name;
        } elseif ($this->record->customer?->name) {
            $customerName = $this->record->customer->name;
        } else {
            $customerName = $this->record->from_number === 'anonymous' ? 'Anonymer Anrufer' : 'Unbekannter Kunde';
        }

        $date = $this->record->created_at?->format('d.m.Y H:i') ?? '';

        return 'Anruf bearbeiten: ' . $customerName . ' - ' . $date;
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Call updated')
            ->body('The call details have been updated successfully.');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
