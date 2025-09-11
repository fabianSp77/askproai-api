<?php

namespace App\Filament\Admin\Resources\PhoneNumberResource\Pages;

use App\Filament\Admin\Resources\PhoneNumberResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPhoneNumbers extends ListRecords
{
    protected static string $resource = PhoneNumberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}