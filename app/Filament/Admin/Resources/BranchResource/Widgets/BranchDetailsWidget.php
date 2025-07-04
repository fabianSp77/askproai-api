<?php

namespace App\Filament\Admin\Resources\BranchResource\Widgets;

use App\Models\PhoneNumber;
use App\Models\Staff;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Model;

class BranchDetailsWidget extends Widget
{
    public ?Model $record = null;

    protected static string $view = 'filament.admin.resources.branch-resource.widgets.branch-details-widget';

    protected function getViewData(): array
    {
        if (!$this->record) {
            return [
                'phoneNumbers' => collect(),
                'staff' => collect(),
                'workingHours' => null,
            ];
        }

        // Telefonnummern der Filiale
        $phoneNumbers = PhoneNumber::withoutGlobalScope(\App\Scopes\TenantScope::class)
            ->where('branch_id', $this->record->id)
            ->where('is_active', true)
            ->get();

        // Mitarbeiter der Filiale
        $staff = Staff::withoutGlobalScope(\App\Scopes\TenantScope::class)
            ->where('branch_id', $this->record->id)
            ->where('is_active', true)
            ->get();

        // Öffnungszeiten
        $workingHours = $this->record->working_hours ?? [
            'monday' => ['open' => '09:00', 'close' => '18:00'],
            'tuesday' => ['open' => '09:00', 'close' => '18:00'],
            'wednesday' => ['open' => '09:00', 'close' => '18:00'],
            'thursday' => ['open' => '09:00', 'close' => '18:00'],
            'friday' => ['open' => '09:00', 'close' => '18:00'],
            'saturday' => ['open' => '10:00', 'close' => '14:00'],
            'sunday' => ['closed' => true],
        ];

        return [
            'phoneNumbers' => $phoneNumbers,
            'staff' => $staff,
            'workingHours' => $workingHours,
        ];
    }
}