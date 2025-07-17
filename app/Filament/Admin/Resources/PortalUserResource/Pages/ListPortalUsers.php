<?php

namespace App\Filament\Admin\Resources\PortalUserResource\Pages;

use App\Filament\Admin\Resources\PortalUserResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPortalUsers extends ListRecords
{
    protected static string $resource = PortalUserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}