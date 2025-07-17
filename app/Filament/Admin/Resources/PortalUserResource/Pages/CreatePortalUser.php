<?php

namespace App\Filament\Admin\Resources\PortalUserResource\Pages;

use App\Filament\Admin\Resources\PortalUserResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePortalUser extends CreateRecord
{
    protected static string $resource = PortalUserResource::class;
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Ensure password is hashed if provided
        if (!empty($data['password'])) {
            $data['password'] = bcrypt($data['password']);
        }
        
        // Ensure permissions is JSON encoded
        if (isset($data['permissions']) && is_array($data['permissions'])) {
            $data['permissions'] = json_encode($data['permissions']);
        }
        
        return $data;
    }
}