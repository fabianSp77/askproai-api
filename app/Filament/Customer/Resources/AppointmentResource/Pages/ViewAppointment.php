<?php

namespace App\Filament\Customer\Resources\AppointmentResource\Pages;

use App\Filament\Customer\Resources\AppointmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewAppointment extends ViewRecord
{
    protected static string $resource = AppointmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No edit or delete actions - company users have read-only access
        ];
    }
}
