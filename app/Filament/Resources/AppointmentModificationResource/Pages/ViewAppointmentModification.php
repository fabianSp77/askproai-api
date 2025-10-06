<?php

namespace App\Filament\Resources\AppointmentModificationResource\Pages;

use App\Filament\Resources\AppointmentModificationResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewAppointmentModification extends ViewRecord
{
    protected static string $resource = AppointmentModificationResource::class;

    protected function getHeaderActions(): array
    {
        // No edit/delete actions - audit trail is immutable
        return [];
    }
}
