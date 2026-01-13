<?php

namespace App\Filament\Resources\ServiceChangeFeeResource\Pages;

use App\Filament\Resources\ServiceChangeFeeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateServiceChangeFee extends CreateRecord
{
    protected static string $resource = ServiceChangeFeeResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
