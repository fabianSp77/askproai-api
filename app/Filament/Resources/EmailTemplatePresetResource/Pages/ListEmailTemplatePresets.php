<?php

namespace App\Filament\Resources\EmailTemplatePresetResource\Pages;

use App\Filament\Resources\EmailTemplatePresetResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEmailTemplatePresets extends ListRecords
{
    protected static string $resource = EmailTemplatePresetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
