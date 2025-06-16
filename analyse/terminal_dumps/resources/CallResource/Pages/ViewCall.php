<?php

namespace App\Filament\Admin\Resources\CallResource\Pages;

use App\Filament\Admin\Resources\CallResource;
use Filament\Resources\Pages\ViewRecord;

class ViewCall extends ViewRecord
{
    protected static string $resource = CallResource::class;

    // Extra: Überschreibe den Title
    public function getTitle(): string
    {
        return 'Anruf-Details: ' . ($this->record->call_id ?? $this->record->id);
    }

    protected function getHeaderWidgets(): array
    {
        return [];
    }
}
