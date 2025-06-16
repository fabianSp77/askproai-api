<?php

namespace App\Filament\Admin\Resources\DummyCompanyResource\Pages;

use App\Filament\Admin\Resources\DummyCompanyResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateDummyCompany extends CreateRecord
{
    protected static string $resource = DummyCompanyResource::class;
}
