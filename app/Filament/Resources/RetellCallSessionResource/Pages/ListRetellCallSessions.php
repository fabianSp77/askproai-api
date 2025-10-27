<?php

namespace App\Filament\Resources\RetellCallSessionResource\Pages;

use App\Filament\Resources\RetellCallSessionResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;

class ListRetellCallSessions extends ListRecords
{
    protected static string $resource = RetellCallSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No create action - calls are created automatically
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            // RetellCallSessionResource\Widgets\CallStatsOverview::class,
        ];
    }
}
