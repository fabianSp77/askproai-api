<?php

namespace App\Filament\Resources\ServiceCaseCategoryResource\Pages;

use App\Filament\Resources\ServiceCaseCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditServiceCaseCategory extends EditRecord
{
    protected static string $resource = ServiceCaseCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->requiresConfirmation()
                ->before(function (Actions\DeleteAction $action) {
                    if ($this->record->cases()->exists()) {
                        \Filament\Notifications\Notification::make()
                            ->title('Kategorie kann nicht gelöscht werden')
                            ->body('Es existieren noch Cases in dieser Kategorie.')
                            ->danger()
                            ->send();
                        $action->cancel();
                    }
                    if ($this->record->children()->exists()) {
                        \Filament\Notifications\Notification::make()
                            ->title('Kategorie kann nicht gelöscht werden')
                            ->body('Es existieren noch Unterkategorien.')
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
