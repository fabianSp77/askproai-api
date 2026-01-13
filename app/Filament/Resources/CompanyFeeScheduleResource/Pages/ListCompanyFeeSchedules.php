<?php

namespace App\Filament\Resources\CompanyFeeScheduleResource\Pages;

use App\Filament\Resources\CompanyFeeScheduleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCompanyFeeSchedules extends ListRecords
{
    protected static string $resource = CompanyFeeScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
