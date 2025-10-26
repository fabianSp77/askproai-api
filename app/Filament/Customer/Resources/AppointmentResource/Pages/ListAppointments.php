<?php

namespace App\Filament\Customer\Resources\AppointmentResource\Pages;

use App\Filament\Customer\Resources\AppointmentResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;

class ListAppointments extends ListRecords
{
    protected static string $resource = AppointmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No create action - company users cannot create appointments
        ];
    }
}
