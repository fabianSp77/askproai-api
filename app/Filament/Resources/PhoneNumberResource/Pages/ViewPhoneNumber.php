<?php

namespace App\Filament\Resources\PhoneNumberResource\Pages;

use App\Filament\Resources\PhoneNumberResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Model;

class ViewPhoneNumber extends ViewRecord
{
    protected static string $resource = PhoneNumberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make()
                ->requiresConfirmation(),
        ];
    }

    public function getTitle(): string
    {
        return 'Phone Number: ' . $this->record->formatted_number;
    }

    public function getHeading(): string
    {
        return $this->record->formatted_number;
    }

    public function getSubheading(): ?string
    {
        $company = $this->record->company->name ?? 'No Company';
        $branch = $this->record->branch->name ?? null;

        return $branch ? "{$company} - {$branch}" : $company;
    }

    protected function resolveRecord($key): \Illuminate\Database\Eloquent\Model
    {
        return static::getResource()::resolveRecordRouteBinding($key)
            ->load(['company', 'branch', 'calls']);
    }
}
