<?php

namespace App\Filament\Admin\Resources\CallCampaignResource\Pages;

use App\Filament\Admin\Resources\CallCampaignResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateCallCampaign extends CreateRecord
{
    protected static string $resource = CallCampaignResource::class;
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = auth()->user()->company_id;
        $data['created_by'] = auth()->id();
        $data['status'] = 'draft';
        $data['total_targets'] = 0;
        $data['calls_completed'] = 0;
        $data['calls_failed'] = 0;
        
        return $data;
    }
}