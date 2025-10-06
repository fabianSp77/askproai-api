<?php

namespace App\Filament\Resources\RetellAgentResource\Pages;

use App\Filament\Resources\RetellAgentResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewRetellAgent extends ViewRecord
{
    protected static string $resource = RetellAgentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
