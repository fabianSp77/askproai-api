<?php

namespace App\Filament\Resources\AdminUpdateResource\Pages;

use App\Filament\Resources\AdminUpdateResource;
use Filament\Resources\Pages\CreateRecord;
use Carbon\Carbon;

class CreateAdminUpdate extends CreateRecord
{
    protected static string $resource = AdminUpdateResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Set creator
        $data['created_by'] = auth()->id();

        // If status is published, set published_at
        if ($data['status'] === 'published' && !$data['published_at']) {
            $data['published_at'] = Carbon::now();
        }

        return $data;
    }

    public function getTitle(): string
    {
        return '➕ Neues Admin Update erstellen';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
