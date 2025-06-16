<?php

namespace App\Filament\Admin\Resources\DummyCompanyResource\Pages;

use App\Filament\Admin\Resources\DummyCompanyResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDummyCompanies extends ListRecords
{
    protected static string $resource = DummyCompanyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
