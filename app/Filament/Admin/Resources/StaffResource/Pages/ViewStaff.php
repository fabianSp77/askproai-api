<?php

namespace App\Filament\Admin\Resources\StaffResource\Pages;

use App\Filament\Admin\Resources\StaffResource;
use App\Filament\Concerns\InteractsWithInfolist;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewStaff extends ViewRecord
{
    use InteractsWithInfolist;
    protected static string $resource = StaffResource::class;


    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}