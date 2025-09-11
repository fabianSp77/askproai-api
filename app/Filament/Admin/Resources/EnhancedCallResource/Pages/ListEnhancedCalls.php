<?php

namespace App\Filament\Admin\Resources\EnhancedCallResource\Pages;

use App\Filament\Admin\Resources\EnhancedCallResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEnhancedCalls extends ListRecords
{
    protected static string $resource = EnhancedCallResource::class;
    
    // Removed custom view to use Filament's default table with pagination
    // protected static string $view = 'filament.admin.resources.enhanced-call-resource.pages.list-enhanced-calls';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
    
    protected function getHeaderWidgets(): array
    {
        return [];
    }
    
    public function getTitle(): string
    {
        return 'Enhanced Call Overview';
    }
    
    // Removed getViewData as we're using default table now
    
    // Keep custom heading
    public function getHeading(): string
    {
        return 'Enhanced Calls';
    }
}
