<?php

namespace App\Filament\Resources\EscalationRuleResource\Pages;

use App\Filament\Resources\EscalationRuleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEscalationRules extends ListRecords
{
    protected static string $resource = EscalationRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
