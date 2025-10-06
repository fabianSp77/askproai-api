<?php

namespace App\Filament\Resources\ServiceStaffAssignmentResource\Pages;

use App\Filament\Resources\ServiceStaffAssignmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListServiceStaffAssignments extends ListRecords
{
    protected static string $resource = ServiceStaffAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
