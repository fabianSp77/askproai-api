<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Resources\CompanyResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCompany extends CreateRecord
{
    protected static string $resource = CompanyResource::class;
}
