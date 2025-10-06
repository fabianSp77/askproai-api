<?php

namespace App\Filament\Resources\PlatformCostResource\Pages;

use App\Filament\Resources\PlatformCostResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePlatformCost extends CreateRecord
{
    protected static string $resource = PlatformCostResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    public function getTitle(): string
    {
        return 'Neue Plattform-Kosten erfassen';
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Plattform-Kosten erfolgreich erfasst';
    }
}