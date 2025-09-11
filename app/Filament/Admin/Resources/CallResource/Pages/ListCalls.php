<?php

namespace App\Filament\Admin\Resources\CallResource\Pages;

use App\Filament\Admin\Resources\CallResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCalls extends ListRecords
{
    protected static string $resource = CallResource::class;
    
    // Removed custom view to use Filament's default table with pagination
    // protected static string $view = 'filament.admin.resources.call-resource.pages.list-calls';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
    
    // Removed getViewData as we're using default table now
    
    // Use standard Filament heading
    public function getHeading(): string
    {
        return 'Calls';
    }
}
