<?php

namespace App\Filament\Admin\Resources\CallCampaignResource\Pages;

use App\Filament\Admin\Resources\CallCampaignResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCallCampaign extends EditRecord
{
    protected static string $resource = CallCampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make()
                ->visible(fn () => $this->getRecord()->status === 'draft'),
        ];
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}