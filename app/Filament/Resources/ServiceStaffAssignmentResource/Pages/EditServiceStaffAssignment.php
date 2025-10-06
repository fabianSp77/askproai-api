<?php

namespace App\Filament\Resources\ServiceStaffAssignmentResource\Pages;

use App\Filament\Resources\ServiceStaffAssignmentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditServiceStaffAssignment extends EditRecord
{
    protected static string $resource = ServiceStaffAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
