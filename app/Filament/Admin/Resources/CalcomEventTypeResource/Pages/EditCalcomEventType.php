<?php

namespace App\Filament\Admin\Resources\CalcomEventTypeResource\Pages;

use App\Filament\Admin\Resources\CalcomEventTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCalcomEventType extends EditRecord
{
    protected static string $resource = CalcomEventTypeResource::class;

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