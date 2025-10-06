<?php

namespace App\Livewire\Calendar;

use Livewire\Component;
use App\Models\Appointment;
use App\Models\Staff;
use App\Models\Service;
use App\Models\Branch;
use App\Services\Booking\AvailabilityService;
use App\Services\CalendarSyncService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AppointmentCalendar extends Component
{
    public $view = 'dayGridMonth';
    public $events = [];
    public $resources = [];
    public $selectedDate;
    public $selectedStaffId = null;
    public $selectedBranchId = null;
    public $selectedServiceId = null;
    public $showModal = false;
    public $modalAppointment = null;
    public $filters = [
        'status' => 'all',
        'service' => 'all',
        'staff' => 'all',
        'branch' => 'all'
    ];

    protected $listeners = [
        'refreshCalendar' => 'loadEvents',
        'appointmentUpdated' => 'handleAppointmentUpdate',
        'appointmentCreated' => 'handleAppointmentCreation',
        'appointmentDeleted' => 'handleAppointmentDeletion',
        'echo:appointments,.appointment.updated' => 'handleRealtimeUpdate',
        'echo:appointments,.appointment.created' => 'handleRealtimeCreation',
        'echo:appointments,.appointment.deleted' => 'handleRealtimeDeletion'
    ];

    public function mount($branchId = null, $staffId = null)
    {
        $this->selectedBranchId = $branchId ?? Auth::user()->branch_id ?? Branch::first()->id;
        $this->selectedStaffId = $staffId;
        $this->selectedDate = Carbon::today()->format('Y-m-d');
        $this->loadEvents();
        $this->loadResources();
    }

    public function loadEvents()
    {
        $cacheKey = "calendar_events_{$this->selectedBranchId}_{$this->selectedStaffId}_{$this->selectedDate}";

        $this->events = Cache::remember($cacheKey, 300, function () {
            $query = Appointment::with(['customer', 'staff', 'service', 'branch'])
                ->where('branch_id', $this->selectedBranchId);

            if ($this->selectedStaffId) {
                $query->where('staff_id', $this->selectedStaffId);
            }

            if ($this->filters['status'] !== 'all') {
                $query->where('status', $this->filters['status']);
            }

            if ($this->filters['service'] !== 'all') {
                $query->where('service_id', $this->filters['service']);
            }

            $startDate = Carbon::parse($this->selectedDate)->startOfMonth()->subWeek();
            $endDate = Carbon::parse($this->selectedDate)->endOfMonth()->addWeek();

            $query->whereBetween('start_at', [$startDate, $endDate]);

            return $query->get()->map(function ($appointment) {
                return [
                    'id' => $appointment->id,
                    'title' => $appointment->customer->name . ' - ' . $appointment->service->name,
                    'start' => $appointment->start_at->toIso8601String(),
                    'end' => $appointment->end_at->toIso8601String(),
                    'backgroundColor' => $this->getStatusColor($appointment->status),
                    'borderColor' => $this->getStatusBorderColor($appointment->status),
                    'resourceId' => $appointment->staff_id,
                    'extendedProps' => [
                        'customer_id' => $appointment->customer_id,
                        'customer_name' => $appointment->customer->name,
                        'customer_phone' => $appointment->customer->phone,
                        'service_id' => $appointment->service_id,
                        'service_name' => $appointment->service->name,
                        'service_duration' => $appointment->service->duration,
                        'staff_id' => $appointment->staff_id,
                        'staff_name' => $appointment->staff->name,
                        'status' => $appointment->status,
                        'notes' => $appointment->notes,
                        'total_price' => $appointment->total_price,
                        'is_recurring' => $appointment->is_recurring,
                        'recurring_pattern' => $appointment->recurring_pattern
                    ],
                    'editable' => $appointment->status !== 'completed' && $appointment->status !== 'cancelled',
                    'droppable' => $appointment->status === 'confirmed' || $appointment->status === 'pending'
                ];
            })->toArray();
        });
    }

    public function loadResources()
    {
        $this->resources = Staff::where('branch_id', $this->selectedBranchId)
            ->where('is_active', true)
            ->get()
            ->map(function ($staff) {
                return [
                    'id' => $staff->id,
                    'title' => $staff->name,
                    'businessHours' => $this->getStaffBusinessHours($staff),
                    'eventColor' => $staff->calendar_color ?? '#3788d8'
                ];
            })
            ->toArray();
    }

    private function getStaffBusinessHours($staff)
    {
        $workingHours = $staff->working_hours ?? [];
        $businessHours = [];

        foreach ($workingHours as $day => $hours) {
            if ($hours['enabled'] ?? false) {
                $businessHours[] = [
                    'daysOfWeek' => [$this->getDayNumber($day)],
                    'startTime' => $hours['start'] ?? '09:00',
                    'endTime' => $hours['end'] ?? '18:00'
                ];
            }
        }

        return $businessHours;
    }

    private function getDayNumber($day)
    {
        $days = [
            'monday' => 1,
            'tuesday' => 2,
            'wednesday' => 3,
            'thursday' => 4,
            'friday' => 5,
            'saturday' => 6,
            'sunday' => 0
        ];

        return $days[strtolower($day)] ?? 1;
    }

    public function updateEvent($eventId, $newStart, $newEnd, $newResourceId = null)
    {
        try {
            DB::beginTransaction();

            $appointment = Appointment::findOrFail($eventId);

            // Check availability before updating
            $availabilityService = app(AvailabilityService::class);
            $newStartCarbon = Carbon::parse($newStart);
            $newEndCarbon = Carbon::parse($newEnd);

            $staffId = $newResourceId ?? $appointment->staff_id;

            if (!$availabilityService->isSlotAvailable(
                $appointment->service_id,
                $appointment->branch_id,
                $newStartCarbon,
                $staffId
            )) {
                $this->dispatch('calendar-error', [
                    'message' => 'Dieser Zeitslot ist nicht verfügbar'
                ]);
                $this->loadEvents();
                return;
            }

            // Update appointment
            $appointment->update([
                'start_at' => $newStartCarbon,
                'end_at' => $newEndCarbon,
                'staff_id' => $staffId
            ]);

            // Sync with external calendars
            if ($appointment->staff->google_calendar_id) {
                app(CalendarSyncService::class)->syncToGoogle($appointment);
            }

            if ($appointment->staff->outlook_calendar_id) {
                app(CalendarSyncService::class)->syncToOutlook($appointment);
            }

            DB::commit();

            // Clear cache
            Cache::forget("calendar_events_{$this->selectedBranchId}_{$this->selectedStaffId}_{$this->selectedDate}");

            $this->dispatch('calendar-success', [
                'message' => 'Termin erfolgreich verschoben'
            ]);

            // Broadcast real-time update
            broadcast(new \App\Events\AppointmentUpdated($appointment))->toOthers();

            $this->loadEvents();

        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('calendar-error', [
                'message' => 'Fehler beim Verschieben des Termins: ' . $e->getMessage()
            ]);
            $this->loadEvents();
        }
    }

    public function createEvent($start, $end, $staffId = null)
    {
        $this->dispatch('open-appointment-modal', [
            'start' => $start,
            'end' => $end,
            'staffId' => $staffId ?? $this->selectedStaffId,
            'branchId' => $this->selectedBranchId
        ]);
    }

    public function deleteEvent($eventId)
    {
        try {
            $appointment = Appointment::findOrFail($eventId);

            if ($appointment->status === 'completed') {
                $this->dispatch('calendar-error', [
                    'message' => 'Abgeschlossene Termine können nicht gelöscht werden'
                ]);
                return;
            }

            $appointment->update(['status' => 'cancelled']);

            // Clear cache
            Cache::forget("calendar_events_{$this->selectedBranchId}_{$this->selectedStaffId}_{$this->selectedDate}");

            $this->dispatch('calendar-success', [
                'message' => 'Termin wurde storniert'
            ]);

            // Broadcast real-time update
            broadcast(new \App\Events\AppointmentDeleted($appointment))->toOthers();

            $this->loadEvents();

        } catch (\Exception $e) {
            $this->dispatch('calendar-error', [
                'message' => 'Fehler beim Löschen des Termins'
            ]);
        }
    }

    public function changeView($view)
    {
        $this->view = $view;
        $this->loadEvents();
    }

    public function navigateDate($direction)
    {
        $date = Carbon::parse($this->selectedDate);

        if ($direction === 'prev') {
            $date->subMonth();
        } else if ($direction === 'next') {
            $date->addMonth();
        } else if ($direction === 'today') {
            $date = Carbon::today();
        }

        $this->selectedDate = $date->format('Y-m-d');
        $this->loadEvents();
    }

    public function applyFilters()
    {
        $this->loadEvents();
    }

    public function openAppointmentDetails($eventId)
    {
        $this->modalAppointment = Appointment::with(['customer', 'staff', 'service'])
            ->findOrFail($eventId);
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->modalAppointment = null;
    }

    // Real-time update handlers
    public function handleRealtimeUpdate($data)
    {
        if ($data['branch_id'] == $this->selectedBranchId) {
            $this->loadEvents();
        }
    }

    public function handleRealtimeCreation($data)
    {
        if ($data['branch_id'] == $this->selectedBranchId) {
            $this->loadEvents();
        }
    }

    public function handleRealtimeDeletion($data)
    {
        if ($data['branch_id'] == $this->selectedBranchId) {
            $this->loadEvents();
        }
    }

    private function getStatusColor($status)
    {
        return match($status) {
            'pending' => '#FFA500',
            'confirmed' => '#4CAF50',
            'in_progress' => '#2196F3',
            'completed' => '#8BC34A',
            'cancelled' => '#F44336',
            'no_show' => '#9E9E9E',
            default => '#607D8B'
        };
    }

    private function getStatusBorderColor($status)
    {
        return match($status) {
            'pending' => '#FF8C00',
            'confirmed' => '#45a049',
            'in_progress' => '#1976D2',
            'completed' => '#7CB342',
            'cancelled' => '#D32F2F',
            'no_show' => '#757575',
            default => '#455A64'
        };
    }

    public function render()
    {
        return view('livewire.calendar.appointment-calendar', [
            'branches' => Branch::where('company_id', Auth::user()->company_id)->get(),
            'staff' => Staff::where('branch_id', $this->selectedBranchId)->get(),
            'services' => Service::where('company_id', Auth::user()->company_id)->get()
        ]);
    }
}