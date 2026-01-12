<?php

namespace App\Filament\Resources\EmailTemplatePresetResource\Pages;

use App\Filament\Resources\EmailTemplatePresetResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEmailTemplatePreset extends EditRecord
{
    protected static string $resource = EmailTemplatePresetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
