<?php

namespace App\Filament\Resources\PlatformCostResource\Pages;

use App\Filament\Resources\PlatformCostResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPlatformCosts extends ListRecords
{
    protected static string $resource = PlatformCostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Neue Kosten erfassen'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            PlatformCostResource\Widgets\PlatformCostOverview::class,
        ];
    }

    public function getTitle(): string
    {
        return 'Plattform-Kosten Übersicht';
    }
}