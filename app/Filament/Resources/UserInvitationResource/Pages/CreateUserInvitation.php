<?php

namespace App\Filament\Resources\UserInvitationResource\Pages;

use App\Filament\Resources\UserInvitationResource;
use App\Models\UserInvitation;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateUserInvitation extends CreateRecord
{
    protected static string $resource = UserInvitationResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Auto-generate token and expiration
        $data['token'] = UserInvitation::generateToken();
        $data['expires_at'] = now()->addHours(72);
        $data['invited_by'] = auth()->id();
        $data['company_id'] = auth()->user()->company_id;
        $data['status'] = 'pending';

        return $data;
    }

    protected function afterCreate(): void
    {
        // Queue the invitation email
        $this->record->inviter->notify(
            new \App\Notifications\UserInvitationNotification($this->record)
        );

        // Show success notification
        \Filament\Notifications\Notification::make()
            ->title('Einladung erfolgreich erstellt')
            ->body('Die Einladungs-E-Mail wurde in die Warteschlange gestellt.')
            ->success()
            ->send();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
