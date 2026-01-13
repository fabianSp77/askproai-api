<?php

namespace App\Filament\Resources\AssignmentGroupResource\Pages;

use App\Filament\Resources\AssignmentGroupResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAssignmentGroups extends ListRecords
{
    protected static string $resource = AssignmentGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
