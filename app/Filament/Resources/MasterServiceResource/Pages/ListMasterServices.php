<?php

namespace App\Filament\Resources\MasterServiceResource\Pages;

use App\Filament\Resources\MasterServiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMasterServices extends ListRecords
{
    protected static string $resource = MasterServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
