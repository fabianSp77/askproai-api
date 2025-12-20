<?php

namespace App\Filament\Resources\ServiceOutputConfigurationResource\Pages;

use App\Filament\Resources\ServiceOutputConfigurationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListServiceOutputConfigurations extends ListRecords
{
    protected static string $resource = ServiceOutputConfigurationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
