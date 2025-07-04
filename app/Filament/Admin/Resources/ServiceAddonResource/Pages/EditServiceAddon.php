<?php

namespace App\Filament\Admin\Resources\ServiceAddonResource\Pages;

use App\Filament\Admin\Resources\ServiceAddonResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditServiceAddon extends EditRecord
{
    protected static string $resource = ServiceAddonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}