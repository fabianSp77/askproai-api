<?php

namespace App\Filament\Admin\Resources\EnhancedCallResource\Pages;

use App\Filament\Admin\Resources\EnhancedCallResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEnhancedCall extends EditRecord
{
    protected static string $resource = EnhancedCallResource::class;
    
    // protected static string $view = 'filament.admin.resources.enhanced-call-resource.pages.edit-enhanced-call';

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
    
    protected function getViewData(): array
    {
        return [
            'record' => $this->record,
        ];
    }
}