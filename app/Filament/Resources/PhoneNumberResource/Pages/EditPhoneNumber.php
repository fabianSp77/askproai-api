<?php

namespace App\Filament\Resources\PhoneNumberResource\Pages;

use App\Filament\Resources\PhoneNumberResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;

class EditPhoneNumber extends EditRecord
{
    protected static string $resource = PhoneNumberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make()
                ->requiresConfirmation(),
        ];
    }

    public function getTitle(): string
    {
        return 'Edit: ' . $this->record->formatted_number;
    }

    public function getHeading(): string
    {
        return 'Edit Phone Number';
    }

    public function getSubheading(): ?string
    {
        return $this->record->formatted_number;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Phone number updated')
            ->body('The phone number has been successfully updated.');
    }

    protected function resolveRecord($key): Model
    {
        return static::getResource()::resolveRecordRouteBinding($key)
            ->load(['company', 'branch']);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Ensure phone_number matches number for compatibility
        if (isset($data['number'])) {
            $data['phone_number'] = $data['number'];
        }

        // Set country code from number if not provided
        if (!isset($data['country_code']) && isset($data['number'])) {
            if (str_starts_with($data['number'], '+49')) {
                $data['country_code'] = '+49';
            }
        }

        return $data;
    }
}
