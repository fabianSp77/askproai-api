<?php

namespace App\Filament\Resources\MasterServiceResource\Pages;

use App\Filament\Resources\MasterServiceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMasterService extends EditRecord
{
    protected static string $resource = MasterServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
