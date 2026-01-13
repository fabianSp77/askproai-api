<?php

namespace App\Filament\Resources\EscalationRuleResource\Pages;

use App\Filament\Resources\EscalationRuleResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateEscalationRule extends CreateRecord
{
    protected static string $resource = EscalationRuleResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = Auth::user()->company_id;

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
