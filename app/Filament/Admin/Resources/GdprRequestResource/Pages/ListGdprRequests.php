<?php

namespace App\Filament\Admin\Resources\GdprRequestResource\Pages;

use App\Filament\Admin\Resources\GdprRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGdprRequests extends ListRecords
{
    protected static string $resource = GdprRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
