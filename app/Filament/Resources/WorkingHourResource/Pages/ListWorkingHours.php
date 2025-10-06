<?php

namespace App\Filament\Resources\WorkingHourResource\Pages;

use App\Filament\Resources\WorkingHourResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListWorkingHours extends ListRecords
{
    protected static string $resource = WorkingHourResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
