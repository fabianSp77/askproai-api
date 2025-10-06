<?php

namespace App\Filament\Resources\PlatformCostResource\Pages;

use App\Filament\Resources\PlatformCostResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPlatformCost extends EditRecord
{
    protected static string $resource = PlatformCostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    public function getTitle(): string
    {
        return 'Plattform-Kosten bearbeiten';
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Plattform-Kosten erfolgreich aktualisiert';
    }
}