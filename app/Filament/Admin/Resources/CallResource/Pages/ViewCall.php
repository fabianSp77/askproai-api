<?php

namespace App\Filament\Admin\Resources\CallResource\Pages;

use App\Filament\Admin\Resources\CallResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Model;

class ViewCall extends ViewRecord
{
    protected static string $resource = CallResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
    
    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Ensure relationships are loaded
        if ($this->record) {
            $this->record->loadMissing([
                'company.billingRate',
                'branch',
                'customer',
                'appointment',
                'mlPrediction'
            ]);
        }
        
        return $data;
    }
}