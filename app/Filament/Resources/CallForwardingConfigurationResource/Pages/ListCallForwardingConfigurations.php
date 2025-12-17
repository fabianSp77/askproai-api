<?php

namespace App\Filament\Resources\CallForwardingConfigurationResource\Pages;

use App\Filament\Resources\CallForwardingConfigurationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCallForwardingConfigurations extends ListRecords
{
    protected static string $resource = CallForwardingConfigurationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Neue Weiterleitung')
                ->icon('heroicon-o-plus'),
        ];
    }
}
