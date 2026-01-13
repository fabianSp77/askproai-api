<?php

namespace App\Filament\Resources\CompanyFeeScheduleResource\Pages;

use App\Filament\Resources\CompanyFeeScheduleResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCompanyFeeSchedule extends CreateRecord
{
    protected static string $resource = CompanyFeeScheduleResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
