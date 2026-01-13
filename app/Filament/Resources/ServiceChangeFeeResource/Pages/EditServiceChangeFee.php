<?php

namespace App\Filament\Resources\ServiceChangeFeeResource\Pages;

use App\Filament\Resources\ServiceChangeFeeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditServiceChangeFee extends EditRecord
{
    protected static string $resource = ServiceChangeFeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make()
                ->visible(fn ($record) => $record->canBeEdited()),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
