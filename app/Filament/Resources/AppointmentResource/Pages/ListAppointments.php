<?php

namespace App\Filament\Resources\AppointmentResource\Pages;

use App\Filament\Resources\AppointmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListAppointments extends ListRecords
{
    protected static string $resource = AppointmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getTableQuery(): Builder
    {
        return parent::getTableQuery()->with([
            'company',
            'branch',
            'customer',
            'staff',
            'service'
        ]);
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Resources\AppointmentResource\Widgets\AppointmentStats::class,
            \App\Filament\Resources\AppointmentResource\Widgets\UpcomingAppointments::class,
        ];
    }
}
