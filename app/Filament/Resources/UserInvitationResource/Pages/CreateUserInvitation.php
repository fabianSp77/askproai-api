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
        // Send invitation email to the invited customer (not the inviter!)
        \Illuminate\Support\Facades\Notification::route('mail', $this->record->email)
            ->notify(new \App\Notifications\UserInvitationNotification($this->record));

        // Update status to 'sent'
        $this->record->update(['status' => 'sent']);

        // Show success notification
        \Filament\Notifications\Notification::make()
            ->title('Einladung erfolgreich erstellt')
            ->body('Die Einladungs-E-Mail wurde an ' . $this->record->email . ' versendet.')
            ->success()
            ->send();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
