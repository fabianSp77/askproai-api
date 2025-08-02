<?php

namespace App\Filament\Admin\Resources\CallResource\Pages;

use App\Filament\Admin\Resources\CallResource;
use Filament\Resources\Pages\ListRecords;

class ListCallsSimple extends ListRecords
{
    protected static string $resource = CallResource::class;
    
    public function mount(): void
    {
        // Force company context
        if (auth()->check() && auth()->user()->company_id) {
            app()->instance('current_company_id', auth()->user()->company_id);
            app()->instance('company_context_source', 'web_auth');
            
            \Log::info('ListCallsSimple: Set company context', [
                'user_id' => auth()->id(),
                'company_id' => auth()->user()->company_id,
            ]);
        }
        
        parent::mount();
    }
    
    protected function getHeaderActions(): array
    {
        return [];
    }
}
