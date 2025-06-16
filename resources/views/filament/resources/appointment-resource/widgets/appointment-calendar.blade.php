<div>
    <div class="fi-wi-stats-overview-card relative rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <div class="mb-4">
            <h2 class="text-lg font-semibold">Terminkalender</h2>
            <p class="text-sm text-gray-500">Übersicht aller Termine im Kalender</p>
        </div>
        
        <div id="appointment-calendar" wire:ignore></div>
    </div>
    
    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const calendarEl = document.getElementById('appointment-calendar');
            
            const calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                locale: 'de',
                height: 650,
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
                events: @json($this->getEvents()),
                eventClick: function(info) {
                    if (info.event.url) {
                        window.location.href = info.event.url;
                        info.jsEvent.preventDefault();
                    }
                },
                eventDidMount: function(info) {
                    // Add tooltip
                    const props = info.event.extendedProps;
                    let tooltipContent = `
                        <div class="p-2">
                            <div><strong>${info.event.title}</strong></div>
                            ${props.staff ? `<div>Mitarbeiter: ${props.staff}</div>` : ''}
                            ${props.price ? `<div>Preis: ${props.price}</div>` : ''}
                            <div>Status: ${getStatusLabel(props.status)}</div>
                        </div>
                    `;
                    
                    info.el.setAttribute('title', tooltipContent.replace(/<[^>]*>/g, ''));
                },
                dayMaxEvents: 3,
                eventTimeFormat: {
                    hour: '2-digit',
                    minute: '2-digit',
                    hour12: false
                },
                slotLabelFormat: {
                    hour: '2-digit',
                    minute: '2-digit',
                    hour12: false
                }
            });
            
            calendar.render();
            
            function getStatusLabel(status) {
                const labels = {
                    'pending': 'Ausstehend',
                    'confirmed': 'Bestätigt',
                    'completed': 'Abgeschlossen',
                    'cancelled': 'Abgesagt',
                    'no_show': 'Nicht erschienen'
                };
                return labels[status] || status;
            }
        });
    </script>
    @endpush
    
    @push('styles')
    <style>
        .fc-event {
            cursor: pointer;
            padding: 2px 4px;
            font-size: 0.875rem;
        }
        .fc-daygrid-event {
            white-space: normal;
        }
        .fc-toolbar-title {
            font-size: 1.25rem;
            font-weight: 600;
        }
        .fc-button {
            background-color: rgb(59 130 246);
            border-color: rgb(59 130 246);
            font-size: 0.875rem;
            padding: 0.25rem 0.75rem;
        }
        .fc-button:hover {
            background-color: rgb(37 99 235);
            border-color: rgb(37 99 235);
        }
        .fc-button-active {
            background-color: rgb(29 78 216) !important;
            border-color: rgb(29 78 216) !important;
        }
        .fc-today {
            background-color: rgb(239 246 255) !important;
        }
        .dark .fc-today {
            background-color: rgb(30 41 59) !important;
        }
    </style>
    @endpush
</div>