<?php

namespace App\Filament\Resources\AppointmentModificationResource\Pages;

use App\Filament\Resources\AppointmentModificationResource;
use App\Filament\Resources\AppointmentModificationResource\Widgets\ModificationStatsWidget;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAppointmentModifications extends ListRecords
{
    protected static string $resource = AppointmentModificationResource::class;

    protected function getHeaderActions(): array
    {
        // No create action - audit trail records are created automatically
        return [];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            ModificationStatsWidget::class,
        ];
    }
}
