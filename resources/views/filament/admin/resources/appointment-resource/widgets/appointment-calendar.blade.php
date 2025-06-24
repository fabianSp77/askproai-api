<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Terminkalender
        </x-slot>

        <x-slot name="description">
            Übersicht aller Termine im aktuellen Monat
        </x-slot>

        <div 
            x-data="{
                appointments: @js($this->getAppointments()),
                init() {
                    // Initialize FullCalendar
                    const calendarEl = this.$refs.calendar;
                    
                    const calendar = new FullCalendar.Calendar(calendarEl, {
                        initialView: 'dayGridMonth',
                        locale: 'de',
                        firstDay: 1,
                        headerToolbar: {
                            left: 'prev,next today',
                            center: 'title',
                            right: 'dayGridMonth,timeGridWeek,listWeek'
                        },
                        buttonText: {
                            today: 'Heute',
                            month: 'Monat',
                            week: 'Woche',
                            list: 'Liste'
                        },
                        events: this.appointments,
                        eventClick: function(info) {
                            const event = info.event;
                            const props = event.extendedProps;
                            
                            // Show appointment details in a modal
                            alert(`
Kunde: ${event.title}
Service: ${props.service || 'Nicht angegeben'}
Mitarbeiter: ${props.staff || 'Nicht zugewiesen'}
Status: ${props.status}
Telefon: ${props.phone || 'Nicht angegeben'}
                            `);
                        },
                        eventDidMount: function(info) {
                            // Add tooltip
                            info.el.setAttribute('title', `${info.event.title} - ${info.event.extendedProps.service || 'Service'}`);
                        },
                        height: 'auto',
                        contentHeight: 600,
                        dayMaxEvents: true,
                        eventTimeFormat: {
                            hour: '2-digit',
                            minute: '2-digit',
                            meridiem: false
                        }
                    });
                    
                    calendar.render();
                }
            }"
            class="appointment-calendar-container"
        >
            <div x-ref="calendar" class="appointment-calendar"></div>
        </div>

        <style>
            .appointment-calendar-container {
                padding: 1rem;
            }
            
            .appointment-calendar .fc {
                font-family: inherit;
            }
            
            .appointment-calendar .fc-button {
                background: white;
                border: 2px solid #e5e7eb;
                color: #374151;
                padding: 0.5rem 1rem;
                border-radius: 0.5rem;
                font-weight: 500;
                transition: all 0.2s ease;
            }
            
            .appointment-calendar .fc-button:hover {
                background: #f9fafb;
                border-color: #d1d5db;
                transform: translateY(-1px);
            }
            
            .appointment-calendar .fc-button-active {
                background: rgb(168, 85, 247) !important;
                border-color: rgb(168, 85, 247) !important;
                color: white !important;
            }
            
            .appointment-calendar .fc-toolbar-title {
                font-size: 1.25rem;
                font-weight: 600;
                color: #374151;
            }
            
            .appointment-calendar .fc-col-header-cell {
                background: #f9fafb;
                padding: 0.75rem;
                font-weight: 600;
                text-transform: uppercase;
                font-size: 0.75rem;
                letter-spacing: 0.05em;
                color: #6b7280;
            }
            
            .appointment-calendar .fc-daygrid-day {
                border: 1px solid #e5e7eb;
            }
            
            .appointment-calendar .fc-daygrid-day:hover {
                background: rgba(168, 85, 247, 0.05);
            }
            
            .appointment-calendar .fc-event {
                border: none;
                padding: 0.25rem 0.5rem;
                border-radius: 0.375rem;
                font-size: 0.875rem;
                cursor: pointer;
                transition: transform 0.2s ease;
            }
            
            .appointment-calendar .fc-event:hover {
                transform: translateY(-1px);
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            }
            
            .appointment-calendar .fc-daygrid-day-number {
                padding: 0.5rem;
                font-weight: 500;
            }
            
            .appointment-calendar .fc-today {
                background: rgba(168, 85, 247, 0.1) !important;
            }
            
            .dark .appointment-calendar .fc {
                background: #1f2937;
                color: #f3f4f6;
            }
            
            .dark .appointment-calendar .fc-button {
                background: #374151;
                border-color: #4b5563;
                color: #f3f4f6;
            }
            
            .dark .appointment-calendar .fc-button:hover {
                background: #4b5563;
                border-color: #6b7280;
            }
            
            .dark .appointment-calendar .fc-toolbar-title {
                color: #f3f4f6;
            }
            
            .dark .appointment-calendar .fc-col-header-cell {
                background: #374151;
                color: #d1d5db;
            }
            
            .dark .appointment-calendar .fc-daygrid-day {
                border-color: #374151;
            }
            
            .dark .appointment-calendar .fc-today {
                background: rgba(168, 85, 247, 0.2) !important;
            }
        </style>

        @push('scripts')
            <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
            <script src="https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.11/locales/de.global.min.js"></script>
        @endpush
    </x-filament::section>
</x-filament-widgets::widget>