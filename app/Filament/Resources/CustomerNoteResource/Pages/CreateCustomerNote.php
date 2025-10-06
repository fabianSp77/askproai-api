<?php

namespace App\Filament\Resources\CustomerNoteResource\Pages;

use App\Filament\Resources\CustomerNoteResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomerNote extends CreateRecord
{
    protected static string $resource = CustomerNoteResource::class;
}
