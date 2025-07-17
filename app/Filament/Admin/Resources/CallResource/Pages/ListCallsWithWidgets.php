<?php

namespace App\Filament\Admin\Resources\CallResource\Pages;

use App\Filament\Admin\Resources\CallResource;
use Filament\Resources\Pages\ListRecords;

class ListCallsWithWidgets extends ListCalls
{
    // Explicitly expose widgets for Filament v3
    public function getHeaderWidgetsColumns(): int | string | array
    {
        return 2;
    }
    
    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Admin\Widgets\CallLiveStatusWidget::class,
            \App\Filament\Admin\Widgets\GlobalFilterWidget::class,
            \App\Filament\Admin\Widgets\CallKpiWidget::class,
            \App\Filament\Admin\Resources\CallResource\Widgets\CallAnalyticsWidget::class,
        ];
    }
    
    // Override to ensure widgets are loaded
    protected function getHeaderWidgetsData(): array
    {
        return [];
    }
}