<?php

namespace App\Filament\Resources\CustomerResource\Pages;

use App\Filament\Resources\CustomerResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewCustomer extends ViewRecord
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    // ✅ RESTORED (2025-10-03) - Widgets were orphaned, never used before
    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Resources\CustomerResource\Widgets\CustomerOverview::class,
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            \App\Filament\Resources\CustomerResource\Widgets\CustomerRiskAlerts::class,
        ];
    }

    public function getTitle(): string
    {
        return $this->record->name ?? 'Kunde anzeigen';
    }
}