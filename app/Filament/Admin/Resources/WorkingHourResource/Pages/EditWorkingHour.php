<?php

namespace App\Filament\Admin\Resources\WorkingHourResource\Pages;

use App\Filament\Admin\Resources\WorkingHourResource;
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
