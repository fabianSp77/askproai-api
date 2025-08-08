<?php

namespace App\Filament\Admin\Resources\SimpleCallResource\Pages;

use App\Filament\Admin\Resources\SimpleCallResource;
use Filament\Resources\Pages\ListRecords;

class ListSimpleCalls extends ListRecords
{
    protected static string $resource = SimpleCallResource::class;
    
    protected function getHeaderActions(): array
    {
        return [];
    }
}