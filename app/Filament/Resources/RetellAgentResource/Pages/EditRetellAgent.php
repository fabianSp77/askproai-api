<?php

namespace App\Filament\Resources\RetellAgentResource\Pages;

use App\Filament\Resources\RetellAgentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRetellAgent extends EditRecord
{
    protected static string $resource = RetellAgentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
