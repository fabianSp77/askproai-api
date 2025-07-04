<?php

namespace App\Filament\Admin\Resources\BillingPeriodResource\Pages;

use App\Filament\Admin\Resources\BillingPeriodResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBillingPeriod extends EditRecord
{
    protected static string $resource = BillingPeriodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
    
    public function mount(int|string $record): void
    {
        parent::mount($record);
    }
}
