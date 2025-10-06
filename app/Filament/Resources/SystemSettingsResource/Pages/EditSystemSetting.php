<?php

namespace App\Filament\Resources\SystemSettingsResource\Pages;

use App\Filament\Resources\SystemSettingsResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Cache;

class EditSystemSetting extends EditRecord
{
    protected static string $resource = SystemSettingsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn () => !in_array($this->record->key, [
                    'site_name',
                    'maintenance_mode',
                    'backup_enabled',
                ])),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = auth()->id();
        
        return $data;
    }

    protected function afterSave(): void
    {
        // Clear cache for this setting
        Cache::forget("setting:{$this->record->key}");
        Cache::forget('settings:all');
        Cache::forget('settings:grouped');

        Notification::make()
            ->title('Einstellung gespeichert')
            ->body("Die Einstellung '{$this->record->label}' wurde erfolgreich aktualisiert.")
            ->success()
            ->send();
    }
}