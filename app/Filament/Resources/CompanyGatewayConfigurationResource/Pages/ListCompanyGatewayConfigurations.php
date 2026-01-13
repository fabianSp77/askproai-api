<?php

namespace App\Filament\Resources\CompanyGatewayConfigurationResource\Pages;

use App\Filament\Resources\CompanyGatewayConfigurationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCompanyGatewayConfigurations extends ListRecords
{
    protected static string $resource = CompanyGatewayConfigurationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Neue Konfiguration'),
        ];
    }
}
