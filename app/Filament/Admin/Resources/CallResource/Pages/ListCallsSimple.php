<?php

namespace App\Filament\Admin\Resources\CallResource\Pages;

use App\Filament\Admin\Resources\CallResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Models\Call;

class ListCallsSimple extends ListRecords
{
    protected static string $resource = CallResource::class;
    
    public function getTitle(): string
    {
        return 'Anrufliste (Simplified)';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Neuer Anruf')
                ->icon('heroicon-o-plus'),
        ];
    }
    
    // No tabs, no filters - just basic table
    
    protected function getTableRecordsPerPageSelectOptions(): array
    {
        return [10, 25, 50, 100];
    }
}