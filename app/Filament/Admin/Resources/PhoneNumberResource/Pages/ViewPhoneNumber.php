<?php

namespace App\Filament\Admin\Resources\PhoneNumberResource\Pages;

use App\Filament\Admin\Resources\PhoneNumberResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use App\Filament\Concerns\InteractsWithInfolist;

class ViewPhoneNumber extends ViewRecord
{
    use InteractsWithInfolist;

    protected static string $resource = PhoneNumberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}