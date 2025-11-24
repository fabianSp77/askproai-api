<?php

namespace App\Filament\Resources\AppointmentResource\Pages;

use App\Events\Appointments\AppointmentRescheduled;
use App\Filament\Resources\AppointmentResource;
use App\Models\Appointment;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Carbon;

class EditAppointment extends EditRecord
{
    protected static string $resource = AppointmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    /**
     * Hook before saving appointment
     * Check for conflicts with existing appointments
     */
    protected function beforeSave(): void
    {
        // Check if staff_id and times are set
        if (!isset($this->data['staff_id']) || !isset($this->data['starts_at']) || !isset($this->data['ends_at'])) {
            return;
        }

        // Check for overlapping appointments with same staff member
        $conflicts = Appointment::where('staff_id', $this->data['staff_id'])
            ->where('id', '!=', $this->record->id) // Exclude current appointment
            ->where('status', '!=', 'cancelled')
            ->where(function ($query) {
                // Check if edited appointment overlaps with existing ones
                $query->where(function ($q) {
                    // Edited appointment starts during existing appointment
                    $q->where('starts_at', '<=', $this->data['starts_at'])
                      ->where('ends_at', '>', $this->data['starts_at']);
                })->orWhere(function ($q) {
                    // Edited appointment ends during existing appointment
                    $q->where('starts_at', '<', $this->data['ends_at'])
                      ->where('ends_at', '>=', $this->data['ends_at']);
                })->orWhere(function ($q) {
                    // Edited appointment completely contains existing appointment
                    $q->where('starts_at', '>=', $this->data['starts_at'])
                      ->where('ends_at', '<=', $this->data['ends_at']);
                });
            })
            ->exists();

        if ($conflicts) {
            Notification::make()
                ->title('⚠️ Konflikt erkannt!')
                ->body('Der Mitarbeiter hat bereits einen Termin zu dieser Zeit. Bitte wählen Sie eine andere Zeit.')
                ->warning()
                ->persistent()
                ->send();

            $this->halt();
        }
    }

    /**
     * Hook after saving appointment
     * Fire AppointmentRescheduled event if time changed
     */
    protected function afterSave(): void
    {
        // Check if appointment time was changed (reschedule)
        $originalStartsAt = $this->record->getOriginal('starts_at');
        $newStartsAt = $this->record->starts_at;

        if ($originalStartsAt && $newStartsAt && $originalStartsAt !== $newStartsAt->toDateTimeString()) {
            // Time was changed - this is a reschedule
            $this->record->update(['sync_origin' => 'admin']);

            // 🔄 Fire AppointmentRescheduled event for Cal.com sync
            event(new AppointmentRescheduled(
                appointment: $this->record,
                oldStartTime: Carbon::parse($originalStartsAt),
                newStartTime: $newStartsAt,
                reason: 'Rescheduled via edit form'
            ));
        }
    }
}
