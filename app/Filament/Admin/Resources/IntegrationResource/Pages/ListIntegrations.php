<?php

namespace App\Filament\Admin\Resources\IntegrationResource\Pages;

use App\Filament\Admin\Resources\IntegrationResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\CreateAction;

class ListIntegrations extends ListRecords
{
    protected static string $resource = IntegrationResource::class;

    /** Kopf-Aktionen (Button-Leiste) */
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),   // „New Integration“
        ];
    }
}
