<?php

namespace App\Filament\Admin\Resources\UnifiedEventTypeResource\Pages;

use App\Filament\Admin\Resources\UnifiedEventTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUnifiedEventType extends EditRecord
{
    protected static string $resource = UnifiedEventTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
