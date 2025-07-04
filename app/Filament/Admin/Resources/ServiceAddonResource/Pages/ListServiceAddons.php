<?php

namespace App\Filament\Admin\Resources\ServiceAddonResource\Pages;

use App\Filament\Admin\Resources\ServiceAddonResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListServiceAddons extends ListRecords
{
    protected static string $resource = ServiceAddonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}