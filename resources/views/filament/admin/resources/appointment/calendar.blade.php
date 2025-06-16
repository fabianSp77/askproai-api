<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Calendar Header Controls --}}
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 p-4">
            <div class="flex flex-wrap items-center justify-between gap-4">
                {{-- View Switcher --}}
                <div class="flex items-center space-x-2">
                    <x-filament::input.wrapper>
                        <x-filament::input.select wire:model.live="calendarView">
                            <option value="dayGridMonth">Monat</option>
                            <option value="timeGridWeek">Woche</option>
                            <option value="timeGridDay">Tag</option>
                            <option value="listWeek">Liste</option>
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                </div>
                
                {{-- Filters --}}
                <div class="flex flex-wrap items-center gap-2">
                    {{-- Staff Filter --}}
                    <x-filament::input.wrapper class="w-48">
                        <x-filament::input.select wire:model.live="selectedStaff" placeholder="Alle Mitarbeiter">
                            <option value="">Alle Mitarbeiter</option>
                            @foreach($staff as $s)
                                <option value="{{ $s['id'] }}">{{ $s['name'] }}</option>
                            @endforeach
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                    
                    {{-- Branch Filter --}}
                    <x-filament::input.wrapper class="w-48">
                        <x-filament::input.select wire:model.live="selectedBranch" placeholder="Alle Filialen">
                            <option value="">Alle Filialen</option>
                            @foreach($branches as $b)
                                <option value="{{ $b['id'] }}">{{ $b['name'] }}</option>
                            @endforeach
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                    
                    {{-- Service Filter --}}
                    <x-filament::input.wrapper class="w-48">
                        <x-filament::input.select wire:model.live="selectedService" placeholder="Alle Services">
                            <option value="">Alle Services</option>
                            @foreach($services as $s)
                                <option value="{{ $s['id'] }}">{{ $s['name'] }}</option>
                            @endforeach
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                </div>
                
                {{-- Toggle Options --}}
                <div class="flex items-center gap-4">
                    <label class="flex items-center space-x-2 cursor-pointer">
                        <input type="checkbox" wire:model.live="showAvailability" class="rounded border-gray-300 text-primary-600 focus:ring-primary-600">
                        <span class="text-sm text-gray-700 dark:text-gray-300">Verfügbarkeiten</span>
                    </label>
                    
                    <label class="flex items-center space-x-2 cursor-pointer">
                        <input type="checkbox" wire:model.live="showRevenue" class="rounded border-gray-300 text-primary-600 focus:ring-primary-600">
                        <span class="text-sm text-gray-700 dark:text-gray-300">Umsatz</span>
                    </label>
                </div>
            </div>
        </div>
        
        {{-- Statistics Bar --}}
        @if($showRevenue)
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
            <div class="bg-white dark:bg-gray-900 rounded-lg shadow-sm border border-gray-200 dark:border-gray-800 p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Termine gesamt</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $statistics['totalAppointments'] }}</p>
                    </div>
                    <div class="p-3 bg-blue-100 dark:bg-blue-900/20 rounded-lg">
                        <x-heroicon-o-calendar class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                    </div>
                </div>
            </div>
            
            <div class="bg-white dark:bg-gray-900 rounded-lg shadow-sm border border-gray-200 dark:border-gray-800 p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Abgeschlossen</p>
                        <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $statistics['completedAppointments'] }}</p>
                    </div>
                    <div class="p-3 bg-green-100 dark:bg-green-900/20 rounded-lg">
                        <x-heroicon-o-check-circle class="w-6 h-6 text-green-600 dark:text-green-400" />
                    </div>
                </div>
            </div>
            
            <div class="bg-white dark:bg-gray-900 rounded-lg shadow-sm border border-gray-200 dark:border-gray-800 p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Umsatz</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">€{{ number_format($statistics['totalRevenue'], 2, ',', '.') }}</p>
                    </div>
                    <div class="p-3 bg-emerald-100 dark:bg-emerald-900/20 rounded-lg">
                        <x-heroicon-o-currency-euro class="w-6 h-6 text-emerald-600 dark:text-emerald-400" />
                    </div>
                </div>
            </div>
            
            <div class="bg-white dark:bg-gray-900 rounded-lg shadow-sm border border-gray-200 dark:border-gray-800 p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Erwartet</p>
                        <p class="text-2xl font-bold text-amber-600 dark:text-amber-400">€{{ number_format($statistics['projectedRevenue'], 2, ',', '.') }}</p>
                    </div>
                    <div class="p-3 bg-amber-100 dark:bg-amber-900/20 rounded-lg">
                        <x-heroicon-o-banknotes class="w-6 h-6 text-amber-600 dark:text-amber-400" />
                    </div>
                </div>
            </div>
            
            <div class="bg-white dark:bg-gray-900 rounded-lg shadow-sm border border-gray-200 dark:border-gray-800 p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Auslastung</p>
                        <p class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">{{ $statistics['utilizationRate'] }}%</p>
                    </div>
                    <div class="p-3 bg-indigo-100 dark:bg-indigo-900/20 rounded-lg">
                        <x-heroicon-o-chart-bar class="w-6 h-6 text-indigo-600 dark:text-indigo-400" />
                    </div>
                </div>
            </div>
            
            <div class="bg-white dark:bg-gray-900 rounded-lg shadow-sm border border-gray-200 dark:border-gray-800 p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">No-Shows</p>
                        <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ $statistics['noShowCount'] }}</p>
                    </div>
                    <div class="p-3 bg-red-100 dark:bg-red-900/20 rounded-lg">
                        <x-heroicon-o-x-circle class="w-6 h-6 text-red-600 dark:text-red-400" />
                    </div>
                </div>
            </div>
        </div>
        @endif
        
        {{-- Calendar Container --}}
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 p-6">
            <div id="calendar" class="fc-theme-filament" wire:ignore></div>
        </div>
        
        {{-- Legend --}}
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 p-4">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-3">Legende</h3>
            <div class="flex flex-wrap gap-4">
                <div class="flex items-center space-x-2">
                    <div class="w-4 h-4 rounded" style="background-color: #34d399; border: 2px solid #34d399;"></div>
                    <span class="text-sm text-gray-600 dark:text-gray-400">Bestätigt</span>
                </div>
                <div class="flex items-center space-x-2">
                    <div class="w-4 h-4 rounded" style="background-color: #3b82f6; border: 2px solid #fbbf24;"></div>
                    <span class="text-sm text-gray-600 dark:text-gray-400">Ausstehend</span>
                </div>
                <div class="flex items-center space-x-2">
                    <div class="w-4 h-4 rounded" style="background-color: #6b7280; border: 2px solid #9ca3af;"></div>
                    <span class="text-sm text-gray-600 dark:text-gray-400">Abgeschlossen</span>
                </div>
                <div class="flex items-center space-x-2">
                    <div class="w-4 h-4 rounded" style="background-color: #ef4444; border: 2px solid #f87171;"></div>
                    <span class="text-sm text-gray-600 dark:text-gray-400">Abgesagt</span>
                </div>
                @if($showAvailability)
                <div class="flex items-center space-x-2">
                    <div class="w-4 h-4 rounded" style="background-color: rgba(34, 197, 94, 0.1); border: 1px solid rgba(34, 197, 94, 0.3);"></div>
                    <span class="text-sm text-gray-600 dark:text-gray-400">Verfügbar</span>
                </div>
                @endif
            </div>
        </div>
    </div>
    
    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@fullcalendar/interaction@6.1.11/index.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@fullcalendar/daygrid@6.1.11/index.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@fullcalendar/timegrid@6.1.11/index.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@fullcalendar/list@6.1.11/index.global.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const calendarEl = document.getElementById('calendar');
            
            const calendar = new FullCalendar.Calendar(calendarEl, {
                plugins: ['interaction', 'dayGrid', 'timeGrid', 'list'],
                initialView: @js($calendarView),
                locale: 'de',
                timeZone: 'Europe/Berlin',
                height: 'auto',
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
                weekNumbers: true,
                weekText: 'KW',
                businessHours: {
                    daysOfWeek: [1, 2, 3, 4, 5],
                    startTime: '08:00',
                    endTime: '18:00'
                },
                slotMinTime: '06:00',
                slotMaxTime: '22:00',
                slotDuration: '00:15:00',
                slotLabelInterval: '01:00',
                slotLabelFormat: {
                    hour: '2-digit',
                    minute: '2-digit',
                    hour12: false
                },
                eventTimeFormat: {
                    hour: '2-digit',
                    minute: '2-digit',
                    hour12: false
                },
                dayMaxEvents: true,
                navLinks: true,
                editable: true,
                droppable: true,
                selectable: true,
                selectMirror: true,
                unselectAuto: true,
                eventDisplay: 'block',
                displayEventTime: true,
                displayEventEnd: true,
                
                // Event sources
                events: @js($appointments),
                
                // Interactions
                select: function(info) {
                    @this.call('handleSlotSelection', info.startStr, info.endStr, info.resource?.id);
                },
                
                eventClick: function(info) {
                    @this.call('handleAppointmentClick', info.event.extendedProps.appointmentId);
                },
                
                eventDrop: function(info) {
                    if (!info.event.extendedProps.editable) {
                        info.revert();
                        return;
                    }
                    
                    @this.call('handleAppointmentDrop', 
                        info.event.extendedProps.appointmentId,
                        info.event.startStr,
                        info.event.endStr,
                        info.newResource?.id
                    );
                },
                
                eventResize: function(info) {
                    if (!info.event.extendedProps.editable) {
                        info.revert();
                        return;
                    }
                    
                    @this.call('handleAppointmentDrop',
                        info.event.extendedProps.appointmentId,
                        info.event.startStr,
                        info.event.endStr
                    );
                },
                
                // Custom event rendering
                eventDidMount: function(info) {
                    // Add tooltip
                    const props = info.event.extendedProps;
                    const tooltipContent = `
                        <div class="p-2">
                            <div class="font-semibold">${props.customer}</div>
                            <div class="text-sm">${props.service}</div>
                            <div class="text-sm">${props.staff} - ${props.branch}</div>
                            <div class="text-sm mt-1">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium" 
                                      style="background-color: ${info.event.borderColor}20; color: ${info.event.borderColor}">
                                    ${props.statusLabel}
                                </span>
                            </div>
                            ${props.revenue ? `<div class="text-sm font-semibold mt-1">€${props.revenue.toFixed(2)}</div>` : ''}
                        </div>
                    `;
                    
                    tippy(info.el, {
                        content: tooltipContent,
                        allowHTML: true,
                        placement: 'top',
                        theme: 'light-border',
                        interactive: true,
                        appendTo: document.body
                    });
                    
                    // Add status icon
                    if (props.checkedIn) {
                        info.el.querySelector('.fc-event-title').insertAdjacentHTML('beforeend', 
                            '<svg class="inline-block w-4 h-4 ml-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>'
                        );
                    }
                },
                
                datesSet: function(dateInfo) {
                    @this.set('currentDate', dateInfo.start.toISOString().split('T')[0]);
                    @this.set('calendarView', dateInfo.view.type);
                }
            });
            
            calendar.render();
            
            // Listen for Livewire events
            Livewire.on('calendar-data-updated', (data) => {
                // Remove all events
                calendar.getEvents().forEach(event => event.remove());
                
                // Add appointments
                data[0].appointments.forEach(appointment => {
                    calendar.addEvent(appointment);
                });
                
                // Add availability slots if enabled
                if (data[0].availableSlots) {
                    data[0].availableSlots.forEach(slot => {
                        calendar.addEvent(slot);
                    });
                }
            });
            
            Livewire.on('calendar-navigate', (data) => {
                calendar.gotoDate(data[0].date);
            });
            
            // Make calendar instance available globally
            window.appointmentCalendar = calendar;
        });
    </script>
    
    <script src="https://unpkg.com/@popperjs/core@2"></script>
    <script src="https://unpkg.com/tippy.js@6"></script>
    <link rel="stylesheet" href="https://unpkg.com/tippy.js@6/themes/light-border.css"/>
    
    <style>
        /* FullCalendar Filament Theme */
        .fc-theme-filament {
            --fc-border-color: rgb(229 231 235);
            --fc-button-bg-color: rgb(59 130 246);
            --fc-button-border-color: rgb(59 130 246);
            --fc-button-hover-bg-color: rgb(37 99 235);
            --fc-button-hover-border-color: rgb(37 99 235);
            --fc-button-active-bg-color: rgb(29 78 216);
            --fc-button-active-border-color: rgb(29 78 216);
        }
        
        .dark .fc-theme-filament {
            --fc-border-color: rgb(55 65 81);
            --fc-page-bg-color: rgb(17 24 39);
            --fc-neutral-bg-color: rgb(31 41 55);
            --fc-neutral-text-color: rgb(209 213 219);
            --fc-button-bg-color: rgb(59 130 246);
            --fc-button-border-color: rgb(59 130 246);
            --fc-button-text-color: white;
            --fc-button-hover-bg-color: rgb(37 99 235);
            --fc-button-hover-border-color: rgb(37 99 235);
        }
        
        .fc .fc-button {
            font-size: 0.875rem;
            font-weight: 500;
            padding: 0.375rem 0.75rem;
            border-radius: 0.375rem;
            transition: all 0.15s;
        }
        
        .fc .fc-button:focus {
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .fc-event {
            cursor: pointer;
            transition: all 0.15s;
        }
        
        .fc-event:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        
        .fc-event.available-slot {
            cursor: pointer;
            border-style: dashed !important;
        }
        
        .fc-event.available-slot:hover {
            background-color: rgba(34, 197, 94, 0.2) !important;
        }
        
        .fc-day-today {
            background-color: rgba(59, 130, 246, 0.05) !important;
        }
        
        .dark .fc-day-today {
            background-color: rgba(59, 130, 246, 0.1) !important;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .fc-toolbar {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .fc-toolbar-chunk {
                display: flex;
                justify-content: center;
            }
            
            .fc-button-group {
                margin: 0 !important;
            }
        }
    </style>
    @endpush
</x-filament-panels::page>