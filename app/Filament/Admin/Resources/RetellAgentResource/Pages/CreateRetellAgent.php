<?php

namespace App\Filament\Admin\Resources\RetellAgentResource\Pages;

use App\Filament\Admin\Resources\RetellAgentResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateRetellAgent extends CreateRecord
{
    protected static string $resource = RetellAgentResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = auth()->user()->company_id;
        
        // Set default capabilities if not provided
        if (empty($data['capabilities'])) {
            $data['capabilities'] = RetellAgentResource::getDefaultCapabilities($data['type']);
        }
        
        return $data;
    }
}