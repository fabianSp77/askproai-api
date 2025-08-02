<?php

namespace App\Filament\Admin\Resources\CallResource\Pages;

use App\Filament\Admin\Resources\CallResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCallsWorking extends ListRecords
{
    protected static string $resource = CallResource::class;
    
    public function mount(): void
    {
        // Set company context first
        if (auth()->check() && auth()->user()->company_id) {
            app()->instance('current_company_id', auth()->user()->company_id);
            app()->instance('company_context_source', 'list_calls_working');
        }
        
        parent::mount();
    }
    
    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('refresh')
                ->label('Aktualisieren')
                ->icon('heroicon-o-arrow-path')
                ->action(fn () => $this->redirect(request()->url()))
        ];
    }
    
    // NO TABS, NO WIDGETS - Just the table
}