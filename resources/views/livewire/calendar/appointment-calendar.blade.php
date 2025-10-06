<div class="calendar-container" wire:ignore.self>
    <div class="calendar-header bg-white rounded-lg shadow-sm p-4 mb-4">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <!-- Calendar Navigation -->
            <div class="flex items-center gap-2">
                <button wire:click="navigateDate('prev')" class="btn btn-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </button>
                <button wire:click="navigateDate('today')" class="btn btn-sm">Heute</button>
                <button wire:click="navigateDate('next')" class="btn btn-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </button>
                <span class="text-lg font-semibold ml-4">
                    {{ \Carbon\Carbon::parse($selectedDate)->locale('de')->isoFormat('MMMM YYYY') }}
                </span>
            </div>

            <!-- View Switcher -->
            <div class="flex items-center gap-2">
                <button wire:click="changeView('dayGridMonth')"
                    class="btn btn-sm {{ $view === 'dayGridMonth' ? 'btn-primary' : 'btn-default' }}">
                    Monat
                </button>
                <button wire:click="changeView('timeGridWeek')"
                    class="btn btn-sm {{ $view === 'timeGridWeek' ? 'btn-primary' : 'btn-default' }}">
                    Woche
                </button>
                <button wire:click="changeView('timeGridDay')"
                    class="btn btn-sm {{ $view === 'timeGridDay' ? 'btn-primary' : 'btn-default' }}">
                    Tag
                </button>
                <button wire:click="changeView('listWeek')"
                    class="btn btn-sm {{ $view === 'listWeek' ? 'btn-primary' : 'btn-default' }}">
                    Liste
                </button>
                @if(count($resources) > 0)
                <button wire:click="changeView('resourceTimelineDay')"
                    class="btn btn-sm {{ $view === 'resourceTimelineDay' ? 'btn-primary' : 'btn-default' }}">
                    Mitarbeiter
                </button>
                @endif
            </div>

            <!-- Filters -->
            <div class="flex items-center gap-2">
                <select wire:model="selectedBranchId" wire:change="loadEvents"
                    class="form-select text-sm">
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                    @endforeach
                </select>

                <select wire:model="selectedStaffId" wire:change="loadEvents"
                    class="form-select text-sm">
                    <option value="">Alle Mitarbeiter</option>
                    @foreach($staff as $member)
                        <option value="{{ $member->id }}">{{ $member->name }}</option>
                    @endforeach
                </select>

                <select wire:model="filters.status" wire:change="applyFilters"
                    class="form-select text-sm">
                    <option value="all">Alle Status</option>
                    <option value="pending">Ausstehend</option>
                    <option value="confirmed">Bestätigt</option>
                    <option value="in_progress">In Bearbeitung</option>
                    <option value="completed">Abgeschlossen</option>
                    <option value="cancelled">Storniert</option>
                </select>
            </div>
        </div>
    </div>

    <!-- FullCalendar Container -->
    <div class="bg-white rounded-lg shadow-sm p-4">
        <div id="appointment-calendar" wire:ignore></div>
    </div>

    <!-- Appointment Details Modal -->
    @if($showModal && $modalAppointment)
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
                aria-hidden="true" wire:click="closeModal"></div>

            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                Termindetails
                            </h3>
                            <div class="mt-4 space-y-3">
                                <div>
                                    <span class="font-semibold">Kunde:</span>
                                    {{ $modalAppointment->customer->name }}
                                </div>
                                <div>
                                    <span class="font-semibold">Service:</span>
                                    {{ $modalAppointment->service->name }}
                                </div>
                                <div>
                                    <span class="font-semibold">Mitarbeiter:</span>
                                    {{ $modalAppointment->staff->name }}
                                </div>
                                <div>
                                    <span class="font-semibold">Zeit:</span>
                                    {{ $modalAppointment->start_at->format('d.m.Y H:i') }} -
                                    {{ $modalAppointment->end_at->format('H:i') }}
                                </div>
                                <div>
                                    <span class="font-semibold">Status:</span>
                                    <span class="px-2 py-1 text-xs rounded-full"
                                        style="background-color: {{ $this->getStatusColor($modalAppointment->status) }}; color: white;">
                                        {{ ucfirst($modalAppointment->status) }}
                                    </span>
                                </div>
                                @if($modalAppointment->notes)
                                <div>
                                    <span class="font-semibold">Notizen:</span>
                                    {{ $modalAppointment->notes }}
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="button" wire:click="closeModal"
                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Schließen
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

@push('scripts')
<script src='https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.11/index.global.min.js'></script>
<script src='https://cdn.jsdelivr.net/npm/@fullcalendar/daygrid@6.1.11/index.global.min.js'></script>
<script src='https://cdn.jsdelivr.net/npm/@fullcalendar/timegrid@6.1.11/index.global.min.js'></script>
<script src='https://cdn.jsdelivr.net/npm/@fullcalendar/list@6.1.11/index.global.min.js'></script>
<script src='https://cdn.jsdelivr.net/npm/@fullcalendar/interaction@6.1.11/index.global.min.js'></script>
<script src='https://cdn.jsdelivr.net/npm/@fullcalendar/resource-timeline@6.1.11/index.global.min.js'></script>

<script>
    document.addEventListener('livewire:initialized', function () {
        let calendarEl = document.getElementById('appointment-calendar');

        let calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: @js($view),
            locale: 'de',
            timeZone: 'Europe/Berlin',
            height: 'auto',
            headerToolbar: false,
            events: @js($events),
            resources: @js($resources),
            editable: true,
            droppable: true,
            dayMaxEvents: true,
            weekNumbers: true,
            weekNumberCalculation: 'ISO',
            slotMinTime: '06:00:00',
            slotMaxTime: '22:00:00',
            slotDuration: '00:15:00',
            slotLabelInterval: '01:00',
            slotLabelFormat: {
                hour: 'numeric',
                minute: '2-digit',
                meridiem: false
            },
            businessHours: {
                daysOfWeek: [1, 2, 3, 4, 5, 6],
                startTime: '08:00',
                endTime: '20:00'
            },
            nowIndicator: true,
            selectable: true,
            selectMirror: true,
            eventClick: function(info) {
                @this.openAppointmentDetails(info.event.id);
            },
            eventDrop: function(info) {
                let newResourceId = null;
                if (info.newResource) {
                    newResourceId = info.newResource.id;
                }
                @this.updateEvent(
                    info.event.id,
                    info.event.start.toISOString(),
                    info.event.end.toISOString(),
                    newResourceId
                );
            },
            eventResize: function(info) {
                @this.updateEvent(
                    info.event.id,
                    info.event.start.toISOString(),
                    info.event.end.toISOString()
                );
            },
            select: function(info) {
                @this.createEvent(
                    info.start.toISOString(),
                    info.end.toISOString(),
                    info.resource ? info.resource.id : null
                );
                calendar.unselect();
            },
            eventDidMount: function(info) {
                // Add tooltip with appointment details
                info.el.setAttribute('title',
                    info.event.extendedProps.customer_name + '\n' +
                    info.event.extendedProps.service_name + '\n' +
                    info.event.extendedProps.staff_name
                );
            },
            datesSet: function(info) {
                @this.selectedDate = info.view.currentStart.toISOString().split('T')[0];
                @this.loadEvents();
            }
        });

        calendar.render();

        // Listen for Livewire updates
        Livewire.on('refreshCalendar', () => {
            calendar.refetchEvents();
        });

        Livewire.on('changeCalendarView', (view) => {
            calendar.changeView(view);
        });

        // Update calendar when events change
        Livewire.on('eventsUpdated', () => {
            calendar.removeAllEvents();
            calendar.addEventSource(@this.events);
        });

        // Store calendar instance for later use
        window.appointmentCalendar = calendar;
    });

    // Handle real-time updates via WebSocket
    if (typeof Echo !== 'undefined') {
        Echo.channel('appointments')
            .listen('.appointment.updated', (e) => {
                if (window.appointmentCalendar) {
                    window.appointmentCalendar.refetchEvents();
                }
            })
            .listen('.appointment.created', (e) => {
                if (window.appointmentCalendar) {
                    window.appointmentCalendar.refetchEvents();
                }
            })
            .listen('.appointment.deleted', (e) => {
                if (window.appointmentCalendar) {
                    window.appointmentCalendar.refetchEvents();
                }
            });
    }
</script>
@endpush

@push('styles')
<style>
    .fc-event {
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .fc-event:hover {
        transform: scale(1.02);
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .fc-timegrid-slot {
        height: 40px;
    }

    .fc-day-today {
        background-color: rgba(59, 130, 246, 0.05) !important;
    }

    .fc-col-header-cell {
        background-color: #f3f4f6;
        font-weight: 600;
    }

    .fc-scrollgrid {
        border-radius: 0.5rem;
        overflow: hidden;
    }

    .fc-button {
        background-color: #3b82f6;
        border-color: #3b82f6;
    }

    .fc-button:hover {
        background-color: #2563eb;
        border-color: #2563eb;
    }

    .fc-button-active {
        background-color: #1d4ed8 !important;
        border-color: #1d4ed8 !important;
    }
</style>
@endpush