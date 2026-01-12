<?php

namespace App\Filament\Resources\EscalationRuleResource\Pages;

use App\Filament\Resources\EscalationRuleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEscalationRule extends EditRecord
{
    protected static string $resource = EscalationRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
