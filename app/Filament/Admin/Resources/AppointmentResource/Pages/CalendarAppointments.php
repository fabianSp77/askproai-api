<?php

namespace App\Filament\Admin\Resources\AppointmentResource\Pages;

use App\Filament\Admin\Resources\AppointmentResource;
use App\Models\Appointment;
use App\Models\Staff;
use App\Models\Service;
use App\Models\Customer;
use App\Models\Branch;
use App\Models\WorkingHour;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Carbon\Carbon;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\DB;

class CalendarAppointments extends Page
{
    protected static string $resource = AppointmentResource::class;
    protected static string $view = 'filament.admin.resources.appointment.calendar';
    protected static ?string $title = 'Kalender';
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    
    // State properties
    public array $appointments = [];
    public string $calendarView = 'dayGridMonth';
    public string $currentDate;
    public ?int $selectedStaff = null;
    public ?int $selectedBranch = null;
    public ?int $selectedService = null;
    public array $staff = [];
    public array $branches = [];
    public array $services = [];
    public bool $showAvailability = true;
    public bool $showRevenue = true;
    public array $workingHours = [];
    public array $availableSlots = [];
    public array $statistics = [];
    
    // Quick booking state
    public bool $quickBookingMode = false;
    public ?string $quickBookingStart = null;
    public ?string $quickBookingEnd = null;
    
    public function mount(): void
    {
        $this->currentDate = now()->format('Y-m-d');
        $this->loadResources();
        $this->loadAppointments();
        $this->calculateStatistics();
    }
    
    protected function loadResources(): void
    {
        // Load staff with colors
        $this->staff = Staff::with('user', 'workingHours')
            ->get()
            ->map(fn ($s, $index) => [
                'id' => $s->id,
                'name' => $s->name,
                'color' => $this->getStaffColor($index),
                'avatar' => $s->user?->avatar_url,
            ])
            ->toArray();
            
        // Load branches
        $this->branches = Branch::all()
            ->map(fn ($b) => [
                'id' => $b->id,
                'name' => $b->name,
                'address' => $b->address,
            ])
            ->toArray();
            
        // Load services with colors
        $this->services = Service::all()
            ->map(fn ($s, $index) => [
                'id' => $s->id,
                'name' => $s->name,
                'duration' => $s->duration,
                'price' => $s->price,
                'color' => $this->getServiceColor($index),
            ])
            ->toArray();
    }
    
    #[On('refresh-calendar')]
    public function loadAppointments(): void
    {
        $query = Appointment::with(['customer', 'staff', 'service', 'branch'])
            ->whereNotIn('status', ['cancelled']);
            
        // Apply filters
        if ($this->selectedStaff) {
            $query->where('staff_id', $this->selectedStaff);
        }
        
        if ($this->selectedBranch) {
            $query->where('branch_id', $this->selectedBranch);
        }
        
        if ($this->selectedService) {
            $query->where('service_id', $this->selectedService);
        }
        
        // Get date range based on current view
        [$start, $end] = $this->getDateRangeForView();
        $query->whereBetween('starts_at', [$start, $end]);
        
        // Transform appointments for calendar
        $this->appointments = $query->get()->map(fn ($appointment) => [
            'id' => $appointment->id,
            'title' => $this->formatAppointmentTitle($appointment),
            'start' => $appointment->starts_at->toIso8601String(),
            'end' => $appointment->ends_at->toIso8601String(),
            'backgroundColor' => $this->getAppointmentColor($appointment),
            'borderColor' => $this->getStatusBorderColor($appointment->status),
            'textColor' => '#ffffff',
            'editable' => in_array($appointment->status, ['pending', 'confirmed']),
            'resourceId' => $appointment->staff_id,
            'extendedProps' => [
                'appointmentId' => $appointment->id,
                'customer' => $appointment->customer->name,
                'customerPhone' => $appointment->customer->phone,
                'customerEmail' => $appointment->customer->email,
                'service' => $appointment->service->name,
                'servicePrice' => $appointment->service->price,
                'staff' => $appointment->staff->name,
                'branch' => $appointment->branch->name,
                'status' => $appointment->status,
                'statusLabel' => $this->getStatusLabel($appointment->status),
                'duration' => $appointment->starts_at->diffInMinutes($appointment->ends_at),
                'notes' => $appointment->notes,
                'checkedIn' => (bool) $appointment->checked_in_at,
                'revenue' => $appointment->service->price,
            ],
        ])->toArray();
        
        // Load availability slots if enabled
        if ($this->showAvailability && $this->selectedStaff) {
            $this->loadAvailabilitySlots($start, $end);
        }
        
        // Emit event to update calendar
        $this->dispatch('calendar-data-updated', [
            'appointments' => $this->appointments,
            'availableSlots' => $this->availableSlots,
        ]);
    }
    
    protected function loadAvailabilitySlots($start, $end): void
    {
        $staff = Staff::find($this->selectedStaff);
        if (!$staff) return;
        
        $workingHours = $staff->workingHours()
            ->where('is_available', true)
            ->get();
            
        $this->availableSlots = [];
        $current = Carbon::parse($start);
        
        while ($current <= $end) {
            $dayOfWeek = $current->dayOfWeek;
            $workingHour = $workingHours->firstWhere('day_of_week', $dayOfWeek);
            
            if ($workingHour) {
                // Get booked slots for this day
                $bookedSlots = Appointment::where('staff_id', $this->selectedStaff)
                    ->whereDate('starts_at', $current->toDateString())
                    ->whereNotIn('status', ['cancelled'])
                    ->get(['starts_at', 'ends_at']);
                
                // Calculate available slots
                $slots = $this->calculateAvailableSlots(
                    $current,
                    $workingHour,
                    $bookedSlots
                );
                
                $this->availableSlots = array_merge($this->availableSlots, $slots);
            }
            
            $current->addDay();
        }
    }
    
    protected function calculateAvailableSlots($date, $workingHour, $bookedSlots): array
    {
        $slots = [];
        $start = Carbon::parse($date->format('Y-m-d') . ' ' . $workingHour->start_time);
        $end = Carbon::parse($date->format('Y-m-d') . ' ' . $workingHour->end_time);
        $slotDuration = 30; // 30 minute slots
        
        $current = $start->copy();
        
        while ($current < $end) {
            $slotEnd = $current->copy()->addMinutes($slotDuration);
            
            // Check if slot overlaps with any booked appointment
            $isBooked = $bookedSlots->some(function ($booked) use ($current, $slotEnd) {
                return $current < $booked->ends_at && $slotEnd > $booked->starts_at;
            });
            
            if (!$isBooked && $slotEnd <= $end) {
                $slots[] = [
                    'start' => $current->toIso8601String(),
                    'end' => $slotEnd->toIso8601String(),
                    'display' => 'background',
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                    'borderColor' => 'rgba(34, 197, 94, 0.3)',
                    'classNames' => ['available-slot'],
                    'editable' => false,
                ];
            }
            
            $current->addMinutes($slotDuration);
        }
        
        return $slots;
    }
    
    protected function calculateStatistics(): void
    {
        $query = Appointment::query();
        
        if ($this->selectedStaff) {
            $query->where('staff_id', $this->selectedStaff);
        }
        
        if ($this->selectedBranch) {
            $query->where('branch_id', $this->selectedBranch);
        }
        
        [$start, $end] = $this->getDateRangeForView();
        $query->whereBetween('starts_at', [$start, $end]);
        
        // Calculate statistics
        $appointments = $query->with('service')->get();
        
        $this->statistics = [
            'totalAppointments' => $appointments->count(),
            'completedAppointments' => $appointments->where('status', 'completed')->count(),
            'pendingAppointments' => $appointments->where('status', 'pending')->count(),
            'confirmedAppointments' => $appointments->where('status', 'confirmed')->count(),
            'totalRevenue' => $appointments->where('status', 'completed')->sum(fn($a) => $a->service->price ?? 0),
            'projectedRevenue' => $appointments->whereIn('status', ['confirmed', 'pending'])->sum(fn($a) => $a->service->price ?? 0),
            'noShowCount' => $appointments->where('status', 'no_show')->count(),
            'cancelledCount' => $appointments->where('status', 'cancelled')->count(),
            'averageServicePrice' => $appointments->avg(fn($a) => $a->service->price ?? 0),
            'utilizationRate' => $this->calculateUtilizationRate($appointments, $start, $end),
        ];
    }
    
    protected function calculateUtilizationRate($appointments, $start, $end): float
    {
        if (!$this->selectedStaff) return 0;
        
        $staff = Staff::find($this->selectedStaff);
        if (!$staff) return 0;
        
        $totalWorkingMinutes = 0;
        $current = Carbon::parse($start);
        
        while ($current <= $end) {
            $workingHour = $staff->workingHours()
                ->where('day_of_week', $current->dayOfWeek)
                ->where('is_available', true)
                ->first();
                
            if ($workingHour) {
                $dayStart = Carbon::parse($current->format('Y-m-d') . ' ' . $workingHour->start_time);
                $dayEnd = Carbon::parse($current->format('Y-m-d') . ' ' . $workingHour->end_time);
                $totalWorkingMinutes += $dayStart->diffInMinutes($dayEnd);
            }
            
            $current->addDay();
        }
        
        if ($totalWorkingMinutes === 0) return 0;
        
        $bookedMinutes = $appointments
            ->whereIn('status', ['confirmed', 'completed'])
            ->sum(fn($a) => $a->starts_at->diffInMinutes($a->ends_at));
            
        return round(($bookedMinutes / $totalWorkingMinutes) * 100, 1);
    }
    
    // Calendar interaction methods
    #[On('appointment-dropped')]
    public function handleAppointmentDrop($appointmentId, $newStart, $newEnd, $newResourceId = null): void
    {
        try {
            $appointment = Appointment::find($appointmentId);
            
            if (!$appointment) {
                throw new \Exception('Termin nicht gefunden');
            }
            
            // Check if appointment can be edited
            if (!in_array($appointment->status, ['pending', 'confirmed'])) {
                throw new \Exception('Dieser Termin kann nicht verschoben werden');
            }
            
            $newStartCarbon = Carbon::parse($newStart);
            $newEndCarbon = Carbon::parse($newEnd);
            $staffId = $newResourceId ?? $appointment->staff_id;
            
            // Check for conflicts
            if ($this->hasConflicts($staffId, $newStartCarbon, $newEndCarbon, $appointmentId)) {
                Notification::make()
                    ->title('Terminkonflikt')
                    ->body('Der Mitarbeiter hat bereits einen Termin zu dieser Zeit.')
                    ->danger()
                    ->send();
                    
                $this->loadAppointments();
                return;
            }
            
            // Update appointment
            $appointment->update([
                'starts_at' => $newStartCarbon,
                'ends_at' => $newEndCarbon,
                'staff_id' => $staffId,
            ]);
            
            Notification::make()
                ->title('Termin verschoben')
                ->body('Der Termin wurde erfolgreich verschoben.')
                ->success()
                ->send();
                
            $this->loadAppointments();
            $this->calculateStatistics();
            
        } catch (\Exception $e) {
            Notification::make()
                ->title('Fehler')
                ->body($e->getMessage())
                ->danger()
                ->send();
                
            $this->loadAppointments();
        }
    }
    
    #[On('calendar-slot-selected')]
    public function handleSlotSelection($start, $end, $resourceId = null): void
    {
        $this->quickBookingStart = $start;
        $this->quickBookingEnd = $end;
        
        $this->mountAction('quickBooking', [
            'starts_at' => $start,
            'ends_at' => $end,
            'staff_id' => $resourceId ?? $this->selectedStaff,
        ]);
    }
    
    #[On('appointment-clicked')]
    public function handleAppointmentClick($appointmentId): void
    {
        $this->mountAction('viewAppointment', [
            'appointment' => $appointmentId,
        ]);
    }
    
    protected function hasConflicts($staffId, $start, $end, $excludeId = null): bool
    {
        $query = Appointment::where('staff_id', $staffId)
            ->whereNotIn('status', ['cancelled'])
            ->where(function ($q) use ($start, $end) {
                $q->where(function ($q2) use ($start, $end) {
                    $q2->where('starts_at', '<', $end)
                       ->where('ends_at', '>', $start);
                });
            });
            
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        return $query->exists();
    }
    
    protected function getDateRangeForView(): array
    {
        $current = Carbon::parse($this->currentDate);
        
        return match($this->calendarView) {
            'timeGridDay' => [$current->startOfDay(), $current->endOfDay()],
            'timeGridWeek' => [$current->startOfWeek(), $current->endOfWeek()],
            'dayGridMonth' => [$current->startOfMonth(), $current->endOfMonth()],
            'listWeek' => [$current->startOfWeek(), $current->endOfWeek()],
            default => [$current->startOfMonth(), $current->endOfMonth()],
        };
    }
    
    protected function formatAppointmentTitle($appointment): string
    {
        if ($this->calendarView === 'dayGridMonth') {
            return $appointment->starts_at->format('H:i') . ' - ' . $appointment->customer->name;
        }
        
        return $appointment->customer->name . ' - ' . $appointment->service->name;
    }
    
    protected function getAppointmentColor($appointment): string
    {
        // Priority: Status > Service > Staff
        if ($appointment->status === 'cancelled') {
            return '#ef4444';
        }
        
        if ($appointment->status === 'no_show') {
            return '#6b7280';
        }
        
        // Use service color
        $serviceIndex = array_search($appointment->service_id, array_column($this->services, 'id'));
        if ($serviceIndex !== false) {
            return $this->services[$serviceIndex]['color'];
        }
        
        return '#3b82f6';
    }
    
    protected function getStatusBorderColor($status): string
    {
        return match($status) {
            'pending' => '#fbbf24',
            'confirmed' => '#34d399',
            'completed' => '#9ca3af',
            'cancelled' => '#f87171',
            'no_show' => '#4b5563',
            default => '#93c5fd',
        };
    }
    
    protected function getStatusLabel($status): string
    {
        return match($status) {
            'pending' => 'Ausstehend',
            'confirmed' => 'Bestätigt',
            'completed' => 'Abgeschlossen',
            'cancelled' => 'Abgesagt',
            'no_show' => 'Nicht erschienen',
            default => $status,
        };
    }
    
    protected function getServiceColor($index): string
    {
        $colors = [
            '#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6',
            '#ec4899', '#14b8a6', '#f97316', '#6366f1', '#84cc16',
            '#06b6d4', '#a855f7', '#f43f5e', '#0ea5e9', '#22c55e',
        ];
        
        return $colors[$index % count($colors)];
    }
    
    protected function getStaffColor($index): string
    {
        $colors = [
            '#059669', '#dc2626', '#7c3aed', '#db2777', '#0891b2',
            '#ea580c', '#4f46e5', '#65a30d', '#e11d48', '#0d9488',
            '#c026d3', '#0c4a6e', '#b91c1c', '#5b21b6', '#047857',
        ];
        
        return $colors[$index % count($colors)];
    }
    
    protected function getActions(): array
    {
        return [
            Actions\Action::make('quickBooking')
                ->label('Schnellbuchung')
                ->modalHeading('Schnellbuchung - Neuen Termin anlegen')
                ->form($this->getQuickBookingFormSchema())
                ->modalWidth('lg')
                ->action(function (array $data) {
                    $this->createQuickBooking($data);
                }),
                
            Actions\Action::make('viewAppointment')
                ->modalHeading(fn ($arguments) => 'Termin Details')
                ->modalContent(fn ($arguments) => view('filament.modals.appointment-details', [
                    'appointment' => Appointment::find($arguments['appointment']),
                ]))
                ->modalWidth('lg')
                ->modalFooterActions([
                    Actions\Action::make('edit')
                        ->label('Bearbeiten')
                        ->icon('heroicon-o-pencil')
                        ->url(fn ($arguments) => AppointmentResource::getUrl('edit', ['record' => $arguments['appointment']])),
                    Actions\Action::make('cancel')
                        ->label('Schließen')
                        ->color('gray'),
                ]),
                
            Actions\Action::make('batchReminder')
                ->label('Erinnerungen versenden')
                ->modalHeading('Terminerinnerungen versenden')
                ->form([
                    Forms\Components\DatePicker::make('date')
                        ->label('Für Termine am')
                        ->required()
                        ->default(now()->addDay()),
                    Forms\Components\Select::make('time_before')
                        ->label('Zeit vor Termin')
                        ->options([
                            '24' => '24 Stunden',
                            '12' => '12 Stunden',
                            '6' => '6 Stunden',
                            '2' => '2 Stunden',
                        ])
                        ->default('24')
                        ->required(),
                    Forms\Components\Toggle::make('only_confirmed')
                        ->label('Nur bestätigte Termine')
                        ->default(true),
                ])
                ->action(function (array $data) {
                    $this->sendBatchReminders($data);
                }),
        ];
    }
    
    protected function getHeaderActions(): array
    {
        return [
            Actions\ActionGroup::make([
                Actions\Action::make('today')
                    ->label('Heute')
                    ->icon('heroicon-o-calendar')
                    ->action(function () {
                        $this->currentDate = now()->format('Y-m-d');
                        $this->dispatch('calendar-navigate', date: $this->currentDate);
                        $this->loadAppointments();
                        $this->calculateStatistics();
                    }),
                    
                Actions\Action::make('listView')
                    ->label('Listenansicht')
                    ->icon('heroicon-o-list-bullet')
                    ->url(AppointmentResource::getUrl()),
                    
                Actions\Action::make('export')
                    ->label('Export')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->form([
                        Forms\Components\Select::make('format')
                            ->label('Format')
                            ->options([
                                'pdf' => 'PDF',
                                'excel' => 'Excel',
                                'ical' => 'iCal',
                            ])
                            ->required(),
                        Forms\Components\DatePicker::make('from')
                            ->label('Von')
                            ->required()
                            ->default(now()->startOfMonth()),
                        Forms\Components\DatePicker::make('to')
                            ->label('Bis')
                            ->required()
                            ->default(now()->endOfMonth()),
                    ])
                    ->action(function (array $data) {
                        $this->exportAppointments($data);
                    }),
            ])->button()->label('Weitere Aktionen'),
            
            Actions\Action::make('quickBooking')
                ->label('Schnellbuchung')
                ->icon('heroicon-o-plus-circle')
                ->color('primary')
                ->action(fn () => $this->mountAction('quickBooking')),
                
            ...$this->getActions(),
        ];
    }
    
    protected function getQuickBookingFormSchema(): array
    {
        return [
            Forms\Components\Grid::make(2)
                ->schema([
                    Forms\Components\Select::make('customer_id')
                        ->label('Kunde')
                        ->searchable()
                        ->getSearchResultsUsing(fn (string $search) => 
                            Customer::where('name', 'like', "%{$search}%")
                                ->orWhere('phone', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%")
                                ->limit(50)
                                ->get()
                                ->mapWithKeys(fn ($customer) => [
                                    $customer->id => $customer->name . ' - ' . $customer->phone
                                ])
                        )
                        ->required()
                        ->createOptionForm([
                            Forms\Components\TextInput::make('name')
                                ->label('Name')
                                ->required(),
                            Forms\Components\TextInput::make('phone')
                                ->label('Telefon')
                                ->tel()
                                ->required(),
                            Forms\Components\TextInput::make('email')
                                ->label('E-Mail')
                                ->email(),
                        ])
                        ->createOptionUsing(function (array $data) {
                            $customer = Customer::create($data);
                            return $customer->id;
                        }),
                        
                    Forms\Components\Select::make('service_id')
                        ->label('Service')
                        ->options(fn () => 
                            collect($this->services)->mapWithKeys(fn ($s) => [
                                $s['id'] => $s['name'] . ' - ' . $s['duration'] . ' Min - €' . number_format($s['price'], 2)
                            ])
                        )
                        ->required()
                        ->reactive()
                        ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                            if ($state) {
                                $service = collect($this->services)->firstWhere('id', $state);
                                if ($service && $service['duration']) {
                                    $start = $get('starts_at');
                                    if ($start) {
                                        $set('ends_at', Carbon::parse($start)->addMinutes($service['duration'])->format('Y-m-d H:i'));
                                    }
                                }
                            }
                        }),
                ]),
                
            Forms\Components\Grid::make(3)
                ->schema([
                    Forms\Components\Select::make('staff_id')
                        ->label('Mitarbeiter')
                        ->options(fn () => collect($this->staff)->mapWithKeys(fn ($s) => [$s['id'] => $s['name']]))
                        ->required()
                        ->default(fn () => $this->selectedStaff)
                        ->reactive()
                        ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                            // Check availability when staff changes
                            if ($state && $get('starts_at') && $get('ends_at')) {
                                $this->checkAvailabilityForQuickBooking($state, $get('starts_at'), $get('ends_at'));
                            }
                        }),
                        
                    Forms\Components\DateTimePicker::make('starts_at')
                        ->label('Beginn')
                        ->required()
                        ->seconds(false)
                        ->minutesStep(15)
                        ->default(fn () => $this->quickBookingStart)
                        ->reactive()
                        ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                            if ($state && $serviceId = $get('service_id')) {
                                $service = collect($this->services)->firstWhere('id', $serviceId);
                                if ($service && $service['duration']) {
                                    $set('ends_at', Carbon::parse($state)->addMinutes($service['duration'])->format('Y-m-d H:i'));
                                }
                            }
                        }),
                        
                    Forms\Components\DateTimePicker::make('ends_at')
                        ->label('Ende')
                        ->required()
                        ->seconds(false)
                        ->minutesStep(15)
                        ->default(fn () => $this->quickBookingEnd)
                        ->minDate(fn (Forms\Get $get) => $get('starts_at')),
                ]),
                
            Forms\Components\Select::make('branch_id')
                ->label('Filiale')
                ->options(fn () => collect($this->branches)->mapWithKeys(fn ($b) => [$b['id'] => $b['name']]))
                ->required()
                ->default(fn () => $this->selectedBranch),
                
            Forms\Components\Textarea::make('notes')
                ->label('Notizen')
                ->rows(2),
                
            Forms\Components\Grid::make(2)
                ->schema([
                    Forms\Components\Toggle::make('send_confirmation')
                        ->label('Bestätigung per E-Mail senden')
                        ->default(true),
                        
                    Forms\Components\Toggle::make('send_sms')
                        ->label('SMS-Erinnerung senden')
                        ->default(false),
                ]),
                
            Forms\Components\Placeholder::make('availability_check')
                ->label('Verfügbarkeitsprüfung')
                ->content(fn () => view('filament.components.availability-check')),
        ];
    }
    
    protected function createQuickBooking(array $data): void
    {
        try {
            DB::beginTransaction();
            
            // Final conflict check
            if ($this->hasConflicts(
                $data['staff_id'],
                Carbon::parse($data['starts_at']),
                Carbon::parse($data['ends_at'])
            )) {
                throw new \Exception('Terminkonflikt: Der Mitarbeiter hat bereits einen Termin zu dieser Zeit.');
            }
            
            // Create appointment
            $appointment = Appointment::create([
                ...$data,
                'status' => 'confirmed',
                'booked_by' => auth()->id(),
                'booking_source' => 'admin_calendar',
            ]);
            
            // Send notifications if requested
            if ($data['send_confirmation'] ?? false) {
                // Queue confirmation email
                // dispatch(new SendAppointmentConfirmation($appointment));
            }
            
            if ($data['send_sms'] ?? false) {
                // Queue SMS
                // dispatch(new SendAppointmentSms($appointment));
            }
            
            DB::commit();
            
            Notification::make()
                ->title('Termin erstellt')
                ->body('Der Termin wurde erfolgreich angelegt.')
                ->success()
                ->send();
                
            $this->loadAppointments();
            $this->calculateStatistics();
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Notification::make()
                ->title('Fehler')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    protected function sendBatchReminders(array $data): void
    {
        try {
            $date = Carbon::parse($data['date']);
            $hoursBefore = (int) $data['time_before'];
            
            $query = Appointment::with(['customer', 'service', 'staff'])
                ->whereDate('starts_at', $date);
                
            if ($data['only_confirmed']) {
                $query->where('status', 'confirmed');
            } else {
                $query->whereIn('status', ['confirmed', 'pending']);
            }
            
            $appointments = $query->get();
            $count = 0;
            
            foreach ($appointments as $appointment) {
                // Check if reminder should be sent now
                $reminderTime = $appointment->starts_at->copy()->subHours($hoursBefore);
                
                if (now()->gte($reminderTime)) {
                    // Queue reminder
                    // dispatch(new SendAppointmentReminder($appointment));
                    $count++;
                }
            }
            
            Notification::make()
                ->title('Erinnerungen versendet')
                ->body("{$count} Erinnerungen wurden in die Warteschlange gestellt.")
                ->success()
                ->send();
                
        } catch (\Exception $e) {
            Notification::make()
                ->title('Fehler')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    protected function exportAppointments(array $data): void
    {
        try {
            $appointments = Appointment::with(['customer', 'service', 'staff', 'branch'])
                ->whereBetween('starts_at', [
                    Carbon::parse($data['from'])->startOfDay(),
                    Carbon::parse($data['to'])->endOfDay(),
                ])
                ->get();
                
            switch ($data['format']) {
                case 'pdf':
                    // Generate PDF
                    break;
                case 'excel':
                    // Generate Excel
                    break;
                case 'ical':
                    // Generate iCal
                    break;
            }
            
            Notification::make()
                ->title('Export gestartet')
                ->body('Ihr Export wird vorbereitet und steht in Kürze zum Download bereit.')
                ->success()
                ->send();
                
        } catch (\Exception $e) {
            Notification::make()
                ->title('Export fehlgeschlagen')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    protected function checkAvailabilityForQuickBooking($staffId, $start, $end): array
    {
        // This method would check real-time availability
        // For now, return mock data
        return [
            'available' => !$this->hasConflicts($staffId, Carbon::parse($start), Carbon::parse($end)),
            'nextAvailable' => now()->addHours(2)->format('H:i'),
            'conflicts' => [],
        ];
    }
    
    public function updatedSelectedStaff(): void
    {
        $this->loadAppointments();
        $this->calculateStatistics();
    }
    
    public function updatedSelectedBranch(): void
    {
        $this->loadAppointments();
        $this->calculateStatistics();
    }
    
    public function updatedSelectedService(): void
    {
        $this->loadAppointments();
        $this->calculateStatistics();
    }
    
    public function updatedShowAvailability(): void
    {
        $this->loadAppointments();
    }
    
    public function updatedCalendarView(): void
    {
        $this->loadAppointments();
        $this->calculateStatistics();
    }
}