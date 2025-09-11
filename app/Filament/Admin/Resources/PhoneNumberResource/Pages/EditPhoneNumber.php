<?php

namespace App\Filament\Admin\Resources\PhoneNumberResource\Pages;

use App\Filament\Admin\Resources\PhoneNumberResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPhoneNumber extends EditRecord
{
    protected static string $resource = PhoneNumberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}