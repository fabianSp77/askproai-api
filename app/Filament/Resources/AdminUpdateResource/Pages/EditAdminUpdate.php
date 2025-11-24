<?php

namespace App\Filament\Resources\AdminUpdateResource\Pages;

use App\Filament\Resources\AdminUpdateResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Carbon\Carbon;

class EditAdminUpdate extends EditRecord
{
    protected static string $resource = AdminUpdateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // If status changed to published, set published_at
        if ($data['status'] === 'published' && !$data['published_at']) {
            $data['published_at'] = Carbon::now();
        }

        // Add edit to changelog
        $changelog = ($this->record->changelog ?? '') . "\n" . Carbon::now()->format('Y-m-d H:i') . " - Bearbeitet von " . auth()->user()->email;
        $data['changelog'] = $changelog;

        return $data;
    }

    public function getTitle(): string
    {
        return '✏️ Admin Update bearbeiten';
    }
}
