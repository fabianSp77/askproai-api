<?php

namespace App\Filament\Admin\Resources\PortalUserResource\Pages;

use App\Filament\Admin\Resources\PortalUserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPortalUser extends EditRecord
{
    protected static string $resource = PortalUserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
    
    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Ensure permissions is JSON encoded
        if (isset($data['permissions']) && is_array($data['permissions'])) {
            $data['permissions'] = json_encode($data['permissions']);
        }
        
        return $data;
    }
}