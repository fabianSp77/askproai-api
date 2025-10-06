<?php

namespace App\Filament\Actions;

use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Service;
use App\Models\Staff;
use App\Models\Branch;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;

class QuickAppointmentAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->name('quick_appointment');
        $this->label('Schnelltermin');
        $this->icon('heroicon-o-calendar-days');
        $this->color('success');
        $this->modalHeading('Schnelltermin erstellen');
        $this->modalDescription('Erstellen Sie schnell einen neuen Termin für diesen Kunden');
        $this->modalWidth('xl');

        $this->form($this->getFormSchema());

        $this->action(function (array $data, Model $record = null) {
            $this->createAppointment($data, $record);
        });
    }

    protected function getFormSchema(): array
    {
        return [
            Forms\Components\Grid::make(2)
                ->schema([
                    Forms\Components\Select::make('customer_id')
                        ->label('Kunde')
                        ->relationship('customer', 'name')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->default(fn (?Model $record) => $record?->id)
                        ->disabled(fn (?Model $record) => $record !== null)
                        ->columnSpan(2),

                    Forms\Components\Select::make('service_id')
                        ->label('Service')
                        ->options(function () {
                            return Service::where('is_active', true)
                                ->where('is_bookable', true)
                                ->pluck('name', 'id');
                        })
                        ->required()
                        ->reactive()
                        ->afterStateUpdated(function ($state, Forms\Set $set) {
                            if ($state) {
                                $service = Service::find($state);
                                if ($service) {
                                    $set('duration', $service->duration);
                                    $set('price', $service->price);
                                }
                            }
                        }),

                    Forms\Components\Select::make('staff_id')
                        ->label('Mitarbeiter')
                        ->options(function (Forms\Get $get) {
                            $serviceId = $get('service_id');
                            $query = Staff::where('is_active', true)
                                ->where('is_bookable', true);

                            if ($serviceId) {
                                $query->whereHas('services', function ($q) use ($serviceId) {
                                    $q->where('services.id', $serviceId);
                                });
                            }

                            return $query->get()->pluck('full_name', 'id');
                        })
                        ->required()
                        ->reactive(),

                    Forms\Components\DatePicker::make('appointment_date')
                        ->label('Datum')
                        ->required()
                        ->native(false)
                        ->minDate(now())
                        ->maxDate(now()->addMonths(3))
                        ->displayFormat('d.m.Y')
                        ->reactive()
                        ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                            $this->updateAvailableSlots($state, $get, $set);
                        }),

                    Forms\Components\Select::make('time_slot')
                        ->label('Zeitslot')
                        ->options(function (Forms\Get $get) {
                            return $this->getAvailableTimeSlots($get);
                        })
                        ->required()
                        ->reactive()
                        ->helperText('Verfügbare Zeiten basierend auf Mitarbeiter-Verfügbarkeit'),

                    Forms\Components\TextInput::make('duration')
                        ->label('Dauer (Minuten)')
                        ->numeric()
                        ->default(30)
                        ->disabled()
                        ->dehydrated(),

                    Forms\Components\TextInput::make('price')
                        ->label('Preis')
                        ->numeric()
                        ->prefix('€')
                        ->disabled()
                        ->dehydrated(),

                    Forms\Components\Select::make('branch_id')
                        ->label('Filiale')
                        ->relationship('branch', 'name')
                        ->default(fn () => auth()->user()->branch_id)
                        ->required(),

                    Forms\Components\Textarea::make('notes')
                        ->label('Notizen')
                        ->rows(3)
                        ->columnSpan(2),

                    Forms\Components\Toggle::make('send_confirmation')
                        ->label('Bestätigung senden')
                        ->default(true)
                        ->helperText('SMS/E-Mail Bestätigung an den Kunden senden'),

                    Forms\Components\Toggle::make('send_reminder')
                        ->label('Erinnerung senden')
                        ->default(true)
                        ->helperText('Erinnerung 24h vorher senden'),
                ]),
        ];
    }

    protected function createAppointment(array $data, ?Model $customer = null): void
    {
        try {
            // Parse the time slot
            $timeSlot = explode('-', $data['time_slot']);
            $startTime = Carbon::parse($data['appointment_date'])->setTimeFromTimeString($timeSlot[0]);
            $endTime = $startTime->copy()->addMinutes($data['duration']);

            // Create the appointment
            $appointment = Appointment::create([
                'customer_id' => $customer?->id ?? $data['customer_id'],
                'service_id' => $data['service_id'],
                'staff_id' => $data['staff_id'],
                'branch_id' => $data['branch_id'],
                'company_id' => auth()->user()->company_id,
                'starts_at' => $startTime,
                'ends_at' => $endTime,
                'status' => 'confirmed',
                'price' => $data['price'],
                'notes' => $data['notes'] ?? null,
                'send_reminder' => $data['send_reminder'] ?? true,
            ]);

            // Send confirmation if requested
            if ($data['send_confirmation'] ?? false) {
                $this->sendConfirmation($appointment);
            }

            Notification::make()
                ->title('Termin erstellt')
                ->body("Termin für {$appointment->customer->name} am {$startTime->format('d.m.Y H:i')} wurde erfolgreich erstellt.")
                ->success()
                ->actions([
                    \Filament\Notifications\Actions\Action::make('view')
                        ->label('Anzeigen')
                        ->url(route('filament.admin.resources.appointments.view', $appointment))
                ])
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Fehler beim Erstellen des Termins')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function getAvailableTimeSlots(Forms\Get $get): array
    {
        $date = $get('appointment_date');
        $staffId = $get('staff_id');
        $duration = $get('duration') ?? 30;

        if (!$date || !$staffId) {
            return [];
        }

        $staff = Staff::find($staffId);
        if (!$staff) {
            return [];
        }

        $dayOfWeek = Carbon::parse($date)->dayOfWeek;
        $workingHours = $staff->workingHours()
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->first();

        if (!$workingHours) {
            return ['Mitarbeiter arbeitet nicht an diesem Tag'];
        }

        // Get existing appointments for this staff on this date
        $existingAppointments = Appointment::where('staff_id', $staffId)
            ->whereDate('starts_at', $date)
            ->whereNotIn('status', ['cancelled', 'no_show'])
            ->get();

        // Generate time slots
        $slots = [];
        $startTime = Carbon::parse($date)->setTimeFromTimeString($workingHours->start_time);
        $endTime = Carbon::parse($date)->setTimeFromTimeString($workingHours->end_time);
        $slotDuration = $duration;

        while ($startTime->copy()->addMinutes($slotDuration)->lte($endTime)) {
            $slotEnd = $startTime->copy()->addMinutes($slotDuration);

            // Check if slot is available
            $isAvailable = true;
            foreach ($existingAppointments as $appointment) {
                if (
                    ($startTime >= $appointment->starts_at && $startTime < $appointment->ends_at) ||
                    ($slotEnd > $appointment->starts_at && $slotEnd <= $appointment->ends_at) ||
                    ($startTime <= $appointment->starts_at && $slotEnd >= $appointment->ends_at)
                ) {
                    $isAvailable = false;
                    break;
                }
            }

            if ($isAvailable) {
                $key = $startTime->format('H:i') . '-' . $slotEnd->format('H:i');
                $slots[$key] = $startTime->format('H:i') . ' - ' . $slotEnd->format('H:i');
            }

            $startTime->addMinutes(15); // Move in 15-minute increments
        }

        return $slots ?: ['Keine verfügbaren Zeitslots'];
    }

    protected function updateAvailableSlots($date, Forms\Get $get, Forms\Set $set): void
    {
        if ($date && $get('staff_id')) {
            $slots = $this->getAvailableTimeSlots($get);
            if (!empty($slots)) {
                $set('time_slot', array_key_first($slots));
            }
        }
    }

    protected function sendConfirmation(Appointment $appointment): void
    {
        // This would integrate with your NotificationManager
        // For now, just log it
        \Log::info('Appointment confirmation would be sent', [
            'appointment_id' => $appointment->id,
            'customer_id' => $appointment->customer_id,
        ]);
    }
}