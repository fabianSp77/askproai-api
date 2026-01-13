<?php

namespace App\Filament\Resources\ServiceFeeTemplateResource\Pages;

use App\Filament\Resources\ServiceFeeTemplateResource;
use Filament\Resources\Pages\CreateRecord;

class CreateServiceFeeTemplate extends CreateRecord
{
    protected static string $resource = ServiceFeeTemplateResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
