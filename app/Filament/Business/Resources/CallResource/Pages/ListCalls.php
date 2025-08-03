<?php

namespace App\Filament\Business\Resources\CallResource\Pages;

use App\Filament\Business\Resources\CallResource;
use Filament\Resources\Pages\ListRecords;

class ListCalls extends ListRecords
{
    protected static string $resource = CallResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No create action for calls - they come from Retell.ai
        ];
    }
}