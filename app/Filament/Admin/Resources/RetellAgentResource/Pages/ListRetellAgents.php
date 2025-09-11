<?php

namespace App\Filament\Admin\Resources\RetellAgentResource\Pages;

use App\Filament\Admin\Resources\RetellAgentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRetellAgents extends ListRecords
{
    protected static string $resource = RetellAgentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}