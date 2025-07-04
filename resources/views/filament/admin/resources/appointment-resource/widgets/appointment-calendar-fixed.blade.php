<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Terminkalender
        </x-slot>

        <x-slot name="description">
            Übersicht aller Termine im aktuellen Monat
        </x-slot>

        <div wire:ignore class="appointment-calendar-container">
            <div id="appointment-calendar-{{ $this->getId() }}" class="appointment-calendar"></div>
        </div>

        <style>
            .appointment-calendar-container {
                padding: 1rem;
                min-height: 600px;
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

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Load FullCalendar if not already loaded
                if (typeof FullCalendar === 'undefined') {
                    const script = document.createElement('script');
                    script.src = 'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js';
                    script.onload = function() {
                        const localeScript = document.createElement('script');
                        localeScript.src = 'https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.11/locales/de.global.min.js';
                        localeScript.onload = function() {
                            initializeCalendar();
                        };
                        document.head.appendChild(localeScript);
                    };
                    document.head.appendChild(script);
                } else {
                    initializeCalendar();
                }
                
                function initializeCalendar() {
                    const calendarEl = document.getElementById('appointment-calendar-{{ $this->getId() }}');
                    if (!calendarEl || calendarEl.hasAttribute('data-calendar-initialized')) {
                        return;
                    }
                    
                    calendarEl.setAttribute('data-calendar-initialized', 'true');
                    
                    const appointments = @json($this->getAppointments());
                    
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
                        events: appointments,
                        eventClick: function(info) {
                            const event = info.event;
                            const props = event.extendedProps;
                            
                            // Create and show modal
                            const modalHtml = `
                                <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                                    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                                        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
                                        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                                        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                                            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                                <div class="sm:flex sm:items-start">
                                                    <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                                                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                                            Termindetails
                                                        </h3>
                                                        <div class="mt-4 space-y-3">
                                                            <div>
                                                                <span class="font-semibold">Kunde:</span> ${event.title}
                                                            </div>
                                                            <div>
                                                                <span class="font-semibold">Service:</span> ${props.service || 'Nicht angegeben'}
                                                            </div>
                                                            <div>
                                                                <span class="font-semibold">Mitarbeiter:</span> ${props.staff || 'Nicht zugewiesen'}
                                                            </div>
                                                            <div>
                                                                <span class="font-semibold">Status:</span> 
                                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" style="background-color: ${event.backgroundColor}20; color: ${event.backgroundColor}">
                                                                    ${props.status}
                                                                </span>
                                                            </div>
                                                            <div>
                                                                <span class="font-semibold">Telefon:</span> ${props.phone || 'Nicht angegeben'}
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                                                <a href="/admin/appointments/${event.id}/edit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-purple-600 text-base font-medium text-white hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 sm:ml-3 sm:w-auto sm:text-sm">
                                                    Bearbeiten
                                                </a>
                                                <button type="button" onclick="this.closest('.fixed').remove()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                                    Schließen
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            `;
                            
                            const modalElement = document.createElement('div');
                            modalElement.innerHTML = modalHtml;
                            document.body.appendChild(modalElement.firstElementChild);
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
            });
        </script>
    </x-filament::section>
</x-filament-widgets::widget>