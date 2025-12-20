<?php

namespace App\Filament\Resources\ServiceCaseResource\Pages;

use App\Filament\Resources\ServiceCaseResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditServiceCase extends EditRecord
{
    protected static string $resource = ServiceCaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
