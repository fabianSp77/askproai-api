<?php

namespace App\Filament\Admin\Resources\CalcomEventTypeResource\Pages;

use App\Filament\Admin\Resources\CalcomEventTypeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCalcomEventType extends CreateRecord
{
    protected static string $resource = CalcomEventTypeResource::class;
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}