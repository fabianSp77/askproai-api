<?php

namespace App\Filament\Resources\CustomerNoteResource\Pages;

use App\Filament\Resources\CustomerNoteResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCustomerNote extends EditRecord
{
    protected static string $resource = CustomerNoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
