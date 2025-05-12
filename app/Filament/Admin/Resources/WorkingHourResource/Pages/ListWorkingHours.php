<?php

namespace App\Filament\Admin\Resources\WorkingHourResource\Pages;

use App\Filament\Admin\Resources\WorkingHourResource;
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
