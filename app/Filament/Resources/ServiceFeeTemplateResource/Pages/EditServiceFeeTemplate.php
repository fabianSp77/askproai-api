<?php

namespace App\Filament\Resources\ServiceFeeTemplateResource\Pages;

use App\Filament\Resources\ServiceFeeTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditServiceFeeTemplate extends EditRecord
{
    protected static string $resource = ServiceFeeTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
