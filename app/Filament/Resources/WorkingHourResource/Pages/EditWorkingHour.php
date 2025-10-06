<?php

namespace App\Filament\Resources\WorkingHourResource\Pages;

use App\Filament\Resources\WorkingHourResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditWorkingHour extends EditRecord
{
    protected static string $resource = WorkingHourResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
