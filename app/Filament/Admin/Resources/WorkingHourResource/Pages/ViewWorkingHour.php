<?php

namespace App\Filament\Admin\Resources\WorkingHourResource\Pages;

use App\Filament\Admin\Resources\WorkingHourResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use App\Filament\Concerns\InteractsWithInfolist;

class ViewWorkingHour extends ViewRecord
{
    use InteractsWithInfolist;

    protected static string $resource = WorkingHourResource::class;


    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}