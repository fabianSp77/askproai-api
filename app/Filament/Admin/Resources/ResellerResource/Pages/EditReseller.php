<?php

namespace App\Filament\Admin\Resources\ResellerResource\Pages;

use App\Filament\Admin\Resources\ResellerResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditReseller extends EditRecord
{
    protected static string $resource = ResellerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make()
                ->before(function (Actions\DeleteAction $action) {
                    $record = $this->getRecord();
                    if ($record->childCompanies()->count() > 0) {
                        $action->cancel();
                        \Filament\Notifications\Notification::make()
                            ->title('Cannot delete reseller')
                            ->body('This reseller has active clients. Please remove or transfer clients first.')
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Reseller updated successfully';
    }
}