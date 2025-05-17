<?php

namespace App\Filament\Admin\Resources\IntegrationResource\Pages;

use App\Filament\Admin\Resources\IntegrationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListIntegrations extends ListRecords
{
    protected static string $resource = IntegrationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
