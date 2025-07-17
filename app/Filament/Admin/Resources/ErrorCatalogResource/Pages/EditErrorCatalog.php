<?php

namespace App\Filament\Admin\Resources\ErrorCatalogResource\Pages;

use App\Filament\Admin\Resources\ErrorCatalogResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditErrorCatalog extends EditRecord
{
    protected static string $resource = ErrorCatalogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
