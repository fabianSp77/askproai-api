<?php

namespace App\Filament\Resources\CustomerNoteResource\Pages;

use App\Filament\Resources\CustomerNoteResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCustomerNotes extends ListRecords
{
    protected static string $resource = CustomerNoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Resources\CustomerNoteResource\Widgets\CustomerNoteStats::class,
        ];
    }
}
