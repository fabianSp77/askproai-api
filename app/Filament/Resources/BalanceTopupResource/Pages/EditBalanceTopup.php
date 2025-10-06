<?php

namespace App\Filament\Resources\BalanceTopupResource\Pages;

use App\Filament\Resources\BalanceTopupResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBalanceTopup extends EditRecord
{
    protected static string $resource = BalanceTopupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
