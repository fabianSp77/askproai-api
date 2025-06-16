<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Wochenübersicht
        </x-slot>

        <x-slot name="headerEnd">
            <x-filament::button
                size="sm"
                icon="heroicon-m-calendar"
                @click="$wire.$refresh()"
            >
                Aktualisieren
            </x-filament::button>
        </x-slot>

        <div class="h-96" wire:ignore>
            <div id="appointment-calendar" class="h-full"></div>
        </div>
    </x-filament::section>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('appointment-calendar');
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'timeGridWeek',
                locale: 'de',
                height: '100%',
                slotMinTime: '07:00:00',
                slotMaxTime: '20:00:00',
                slotDuration: '00:30:00',
                businessHours: {
                    daysOfWeek: [1, 2, 3, 4, 5, 6],
                    startTime: '08:00',
                    endTime: '18:00',
                },
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'timeGridDay,timeGridWeek'
                },
                events: @json($this->getAppointments()),
                eventClick: function(info) {
                    const props = info.event.extendedProps;
                    const content = `
                        <div class="p-4 space-y-2">
                            <div><strong>Kunde:</strong> ${props.customer || 'Unbekannt'}</div>
                            ${props.phone ? `<div><strong>Telefon:</strong> ${props.phone}</div>` : ''}
                            ${props.staff ? `<div><strong>Mitarbeiter:</strong> ${props.staff}</div>` : ''}
                            ${props.service ? `<div><strong>Leistung:</strong> ${props.service}</div>` : ''}
                            ${props.price ? `<div><strong>Preis:</strong> ${props.price}</div>` : ''}
                            <div><strong>Status:</strong> <span class="px-2 py-1 text-xs rounded-full" style="background-color: ${info.event.backgroundColor}20; color: ${info.event.backgroundColor}">${props.statusLabel}</span></div>
                        </div>
                        <div class="mt-4 pt-4 border-t flex justify-end gap-2">
                            <a href="/admin/appointments/${info.event.id}/edit" class="px-3 py-1 bg-primary-600 text-white rounded hover:bg-primary-700">Bearbeiten</a>
                        </div>
                    `;
                    
                    // Simple modal
                    const modal = document.createElement('div');
                    modal.className = 'fixed inset-0 z-50 flex items-center justify-center p-4 bg-black bg-opacity-50';
                    modal.innerHTML = `
                        <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
                            <div class="flex justify-between items-center p-4 border-b">
                                <h3 class="text-lg font-semibold">Termindetails</h3>
                                <button onclick="this.closest('.fixed').remove()" class="text-gray-400 hover:text-gray-600">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>
                            ${content}
                        </div>
                    `;
                    document.body.appendChild(modal);
                }
            });
            calendar.render();
        });
    </script>
    @endpush
</x-filament-widgets::widget>