<?php

namespace App\Filament\Customer\Resources\CallHistoryResource\Pages;

use App\Filament\Customer\Resources\CallHistoryResource;
use Filament\Resources\Pages\ViewRecord;

class ViewCallHistory extends ViewRecord
{
    protected static string $resource = CallHistoryResource::class;

    protected static ?string $title = 'Anruf-Details';
}
