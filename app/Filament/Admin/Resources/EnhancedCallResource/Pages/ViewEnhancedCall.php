<?php

namespace App\Filament\Admin\Resources\EnhancedCallResource\Pages;

use App\Filament\Admin\Resources\EnhancedCallResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use App\Filament\Concerns\InteractsWithInfolist;

class ViewEnhancedCall extends ViewRecord
{
    use InteractsWithInfolist;

    protected static string $resource = EnhancedCallResource::class;
    
    // protected static string $view = 'filament.admin.resources.enhanced-call-resource.pages.view-enhanced-call-perfect';

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
    
    public function getTitle(): string
    {
        return $this->record ? ('Anrufdetails #' . $this->record->id) : 'Anrufdetails';
    }
    
    // Hide Filament's default heading to use custom design
    public function getHeading(): string
    {
        return '';
    }
    
    protected function getViewData(): array
    {
        $record = $this->record;
        
        // Ensure all relationships are loaded
        $record->load(['customer', 'appointment', 'agent', 'branch']);
        
        return [
            'record' => $record,
            'call' => $record, // Alias for easier access in view
        ];
    }
}