<?php

namespace App\Filament\Resources\RoleResource\Pages;

use App\Filament\Resources\RoleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make()
                ->visible(fn () => $this->record->can_delete),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Ensure guard_name is set
        $data['guard_name'] = $data['guard_name'] ?? 'web';
        
        return $data;
    }

    protected function afterSave(): void
    {
        Notification::make()
            ->title('Rolle aktualisiert')
            ->body('Die Rolle wurde erfolgreich gespeichert.')
            ->success()
            ->send();
    }
}