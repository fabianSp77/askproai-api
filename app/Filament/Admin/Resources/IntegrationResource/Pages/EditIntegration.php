<?php

namespace App\Filament\Admin\Resources\IntegrationResource\Pages;

use App\Filament\Admin\Resources\IntegrationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditIntegration extends EditRecord
{
    protected static string $resource = IntegrationResource::class;
    
    // protected static string $view = 'filament.admin.resources.integration-resource.pages.edit-integration';

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
