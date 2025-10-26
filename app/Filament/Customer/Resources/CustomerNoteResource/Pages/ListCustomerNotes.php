<?php

namespace App\Filament\Customer\Resources\CustomerNoteResource\Pages;

use App\Filament\Customer\Resources\CustomerNoteResource;
use Filament\Resources\Pages\ListRecords;

class ListCustomerNotes extends ListRecords
{
    protected static string $resource = CustomerNoteResource::class;
}
