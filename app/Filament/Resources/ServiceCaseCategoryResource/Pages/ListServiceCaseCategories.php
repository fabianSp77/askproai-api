<?php

namespace App\Filament\Resources\ServiceCaseCategoryResource\Pages;

use App\Filament\Resources\ServiceCaseCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListServiceCaseCategories extends ListRecords
{
    protected static string $resource = ServiceCaseCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
