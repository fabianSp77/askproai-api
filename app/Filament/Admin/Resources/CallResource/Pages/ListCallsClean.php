<?php

namespace App\Filament\Admin\Resources\CallResource\Pages;

use App\Filament\Admin\Resources\CallResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCallsClean extends ListRecords
{
    protected static string $resource = CallResource::class;
    
    public function mount(): void
    {
        // Force company context
        if (auth()->check() && auth()->user()->company_id) {
            app()->instance('current_company_id', auth()->user()->company_id);
            app()->instance('company_context_source', 'web_auth');
        }
        
        parent::mount();
    }
    
    protected function getHeaderActions(): array
    {
        return [];
    }
}