<?php

namespace App\Filament\Resources\AppointmentResource\Pages;

use App\Events\Appointments\AppointmentBooked;
use App\Filament\Resources\AppointmentResource;
use App\Models\Appointment;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateAppointment extends CreateRecord
{
    protected static string $resource = AppointmentResource::class;

    /**
     * Hook before saving appointment
     * Check for conflicts with existing appointments
     */
    protected function beforeCreate(): void
    {
        // Check if staff_id and times are set
        if (!isset($this->data['staff_id']) || !isset($this->data['starts_at']) || !isset($this->data['ends_at'])) {
            return;
        }

        // Check for overlapping appointments with same staff member
        $conflicts = Appointment::where('staff_id', $this->data['staff_id'])
            ->where('status', '!=', 'cancelled')
            ->where(function ($query) {
                // Check if new appointment overlaps with existing ones
                $query->where(function ($q) {
                    // New appointment starts during existing appointment
                    $q->where('starts_at', '<=', $this->data['starts_at'])
                      ->where('ends_at', '>', $this->data['starts_at']);
                })->orWhere(function ($q) {
                    // New appointment ends during existing appointment
                    $q->where('starts_at', '<', $this->data['ends_at'])
                      ->where('ends_at', '>=', $this->data['ends_at']);
                })->orWhere(function ($q) {
                    // New appointment completely contains existing appointment
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
     * Hook after creating appointment
     * Fire AppointmentBooked event for Cal.com sync
     */
    protected function afterCreate(): void
    {
        // Set sync origin to 'admin' (created via Admin UI)
        $this->record->update(['sync_origin' => 'admin']);

        // 🔄 Fire AppointmentBooked event for Cal.com sync
        event(new AppointmentBooked($this->record));
    }
}
