<?php

namespace App\Filament\Admin\Resources\PromptTemplateResource\Pages;

use App\Filament\Admin\Resources\PromptTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPromptTemplate extends ViewRecord
{
    protected static string $resource = PromptTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}