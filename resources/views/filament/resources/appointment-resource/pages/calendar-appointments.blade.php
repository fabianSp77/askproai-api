<x-filament-panels::page>
    <div class="space-y-4">
        {{-- Calendar Container --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-6">
            <div id="appointment-calendar" class="min-h-[600px]"></div>
        </div>
        
        {{-- Legend --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-4">
            <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-3">Status-Legende</h3>
            <div class="flex flex-wrap gap-4">
                <div class="flex items-center space-x-2">
                    <div class="w-4 h-4 rounded bg-green-500"></div>
                    <span class="text-sm text-gray-600 dark:text-gray-400">Bestätigt/Abgeschlossen</span>
                </div>
                <div class="flex items-center space-x-2">
                    <div class="w-4 h-4 rounded bg-amber-500"></div>
                    <span class="text-sm text-gray-600 dark:text-gray-400">Ausstehend</span>
                </div>
                <div class="flex items-center space-x-2">
                    <div class="w-4 h-4 rounded bg-red-500"></div>
                    <span class="text-sm text-gray-600 dark:text-gray-400">Abgesagt</span>
                </div>
                <div class="flex items-center space-x-2">
                    <div class="w-4 h-4 rounded bg-gray-500"></div>
                    <span class="text-sm text-gray-600 dark:text-gray-400">Nicht erschienen</span>
                </div>
            </div>
        </div>
    </div>

    {{-- FullCalendar CSS --}}
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.9/index.global.min.css" rel="stylesheet">
    
    {{-- FullCalendar JS --}}
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.9/index.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.9/locales/de.global.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const calendarEl = document.getElementById('appointment-calendar');
            const appointments = @json($appointments);
            
            const calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                locale: 'de',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
                },
                buttonText: {
                    today: 'Heute',
                    month: 'Monat',
                    week: 'Woche',
                    day: 'Tag',
                    list: 'Liste'
                },
                events: appointments,
                height: 'auto',
                nowIndicator: true,
                editable: true,
                droppable: true,
                dayMaxEvents: true,
                weekNumbers: true,
                weekText: 'KW',
                businessHours: {
                    daysOfWeek: [1, 2, 3, 4, 5],
                    startTime: '08:00',
                    endTime: '18:00',
                },
                slotMinTime: '07:00',
                slotMaxTime: '21:00',
                slotDuration: '00:15:00',
                slotLabelInterval: '01:00',
                allDaySlot: false,
                
                // Event handling
                eventClick: function(info) {
                    const event = info.event;
                    const props = event.extendedProps;
                    
                    // Show event details modal
                    window.$wireui.confirmDialog({
                        title: event.title,
                        description: `
                            <div class="space-y-2 text-sm">
                                <div><strong>Kunde:</strong> ${props.customer}</div>
                                <div><strong>Service:</strong> ${props.service || 'N/A'}</div>
                                <div><strong>Mitarbeiter:</strong> ${props.staff || 'N/A'}</div>
                                <div><strong>Filiale:</strong> ${props.branch || 'N/A'}</div>
                                <div><strong>Zeit:</strong> ${event.start.toLocaleString('de-DE')} - ${event.end.toLocaleString('de-DE')}</div>
                                <div><strong>Status:</strong> ${props.status}</div>
                                ${props.phone ? `<div><strong>Telefon:</strong> ${props.phone}</div>` : ''}
                                ${props.email ? `<div><strong>E-Mail:</strong> ${props.email}</div>` : ''}
                            </div>
                        `,
                        acceptLabel: 'Bearbeiten',
                        rejectLabel: 'Schließen',
                        onAccept: () => {
                            window.location.href = `/admin/appointments/${event.id}/edit`;
                        }
                    });
                },
                
                eventDrop: function(info) {
                    // Handle drag & drop rescheduling
                    if (confirm(`Möchten Sie den Termin auf ${info.event.start.toLocaleString('de-DE')} verschieben?`)) {
                        // Update appointment via AJAX
                        @this.call('rescheduleAppointment', info.event.id, info.event.start, info.event.end);
                    } else {
                        info.revert();
                    }
                },
                
                dateClick: function(info) {
                    // Quick appointment creation
                    window.location.href = `/admin/appointments/create?date=${info.dateStr}`;
                },
                
                eventDidMount: function(info) {
                    // Add tooltips
                    info.el.setAttribute('title', `${info.event.extendedProps.customer} - ${info.event.extendedProps.service || 'N/A'}`);
                }
            });
            
            calendar.render();
            
            // Listen for navigation events
            window.addEventListener('calendar-go-to-today', () => {
                calendar.today();
            });
        });
    </script>
    
    <style>
        /* Custom calendar styles */
        .fc-event {
            cursor: pointer;
            padding: 2px 4px;
            font-size: 0.875rem;
        }
        
        .fc-daygrid-event {
            white-space: normal;
        }
        
        .fc-time-grid-event {
            overflow: hidden;
        }
        
        .fc-event-title {
            font-weight: 500;
        }
        
        .fc-day-today {
            background-color: rgb(254 249 195 / 0.1) !important;
        }
        
        .dark .fc-day-today {
            background-color: rgb(254 249 195 / 0.05) !important;
        }
        
        /* Dark mode support */
        .dark .fc {
            color: rgb(229 231 235);
        }
        
        .dark .fc-theme-standard td,
        .dark .fc-theme-standard th {
            border-color: rgb(55 65 81);
        }
        
        .dark .fc-theme-standard .fc-scrollgrid {
            border-color: rgb(55 65 81);
        }
        
        .dark .fc-button {
            background-color: rgb(55 65 81);
            border-color: rgb(75 85 99);
            color: rgb(229 231 235);
        }
        
        .dark .fc-button:hover {
            background-color: rgb(75 85 99);
            border-color: rgb(107 114 128);
        }
        
        .dark .fc-button-active {
            background-color: rgb(59 130 246) !important;
            border-color: rgb(59 130 246) !important;
        }
        
        .dark .fc-col-header-cell {
            background-color: rgb(31 41 55);
        }
        
        .dark .fc-daygrid-day-number,
        .dark .fc-col-header-cell-cushion {
            color: rgb(229 231 235);
        }
        
        .dark .fc-daygrid-day.fc-day-today {
            background-color: rgb(59 130 246 / 0.1);
        }
    </style>
</x-filament-panels::page>