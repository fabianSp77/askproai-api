<?php

namespace App\Filament\Resources\CompanyFeeScheduleResource\Pages;

use App\Filament\Resources\CompanyFeeScheduleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCompanyFeeSchedule extends EditRecord
{
    protected static string $resource = CompanyFeeScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
