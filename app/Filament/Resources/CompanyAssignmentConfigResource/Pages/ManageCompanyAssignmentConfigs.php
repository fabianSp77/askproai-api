<?php

namespace App\Filament\Resources\CompanyAssignmentConfigResource\Pages;

use App\Filament\Resources\CompanyAssignmentConfigResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageCompanyAssignmentConfigs extends ManageRecords
{
    protected static string $resource = CompanyAssignmentConfigResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
