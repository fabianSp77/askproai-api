<?php

namespace App\Filament\Resources\ServiceOutputConfigurationResource\Pages;

use App\Filament\Resources\ServiceOutputConfigurationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditServiceOutputConfiguration extends EditRecord
{
    protected static string $resource = ServiceOutputConfigurationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->requiresConfirmation()
                ->before(function (Actions\DeleteAction $action) {
                    if ($this->record->categories()->exists()) {
                        \Filament\Notifications\Notification::make()
                            ->title('Konfiguration kann nicht geloescht werden')
                            ->body('Es existieren noch Kategorien, die diese Konfiguration verwenden.')
                            ->danger()
                            ->send();
                        $action->cancel();
                    }
                }),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
