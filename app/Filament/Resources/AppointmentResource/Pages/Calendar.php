<?php

namespace App\Filament\Resources\AppointmentResource\Pages;

use App\Filament\Resources\AppointmentResource;
use Filament\Resources\Pages\Page;

class Calendar extends Page
{
    protected static string $resource = AppointmentResource::class;

    protected static string $view = 'filament.resources.appointment-resource.pages.calendar';

    protected static ?string $title = 'Kalender';

    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    protected static ?string $navigationLabel = 'Kalender';

    protected static ?int $navigationSort = 2;

    protected function getHeaderWidgets(): array
    {
        return [];
    }

    public function getHeading(): string
    {
        return 'Termin Kalender';
    }

    public function getSubheading(): ?string
    {
        return 'Verwalten Sie alle Termine in der Kalenderansicht';
    }
}