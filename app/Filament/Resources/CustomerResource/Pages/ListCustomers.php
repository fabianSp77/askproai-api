<?php

namespace App\Filament\Resources\CustomerResource\Pages;

use App\Filament\Resources\CustomerResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCustomers extends ListRecords
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        // ⚠️ DISABLED: CustomerStatsOverview uses columns that don't exist in Sept 21 backup
        // Missing columns: status, is_vip, total_revenue, journey_status
        // TODO: Re-enable when database is fully restored
        return [
            // \App\Filament\Widgets\CustomerStatsOverview::class,
        ];
    }
}
