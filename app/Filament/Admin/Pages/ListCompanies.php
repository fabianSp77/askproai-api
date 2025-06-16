<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Resources\CompanyResource;
use Filament\Resources\Pages\ListRecords;

class ListCompanies extends ListRecords
{
    protected static string $resource = CompanyResource::class;
}
