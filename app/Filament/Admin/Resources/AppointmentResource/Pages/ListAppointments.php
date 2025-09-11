<?php

namespace App\Filament\Admin\Resources\AppointmentResource\Pages;

use App\Filament\Admin\Resources\AppointmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAppointments extends ListRecords
{
    protected static string $resource = AppointmentResource::class;
    
    // Removed custom view to use Filament's default table with pagination
    // protected static string $view = 'filament.admin.resources.appointment-resource.pages.list-appointments';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
    
    // Removed getViewData as we're using default table now
    
    // Use standard Filament heading
    public function getHeading(): string
    {
        return 'Appointments';
    }
}
