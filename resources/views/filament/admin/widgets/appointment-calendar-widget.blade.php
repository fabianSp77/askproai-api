<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center justify-between">
                <span>Heutige Termine</span>
                <x-filament::link
                    href="{{ \App\Filament\Admin\Resources\AppointmentResource::getUrl('calendar') }}"
                    tag="a"
                    color="primary"
                    size="sm"
                >
                    Kalender öffnen
                </x-filament::link>
            </div>
        </x-slot>
        
        <x-slot name="headerEnd">
            <div class="flex items-center space-x-4 text-sm">
                <div>
                    <span class="text-gray-500">Heute:</span>
                    <span class="font-semibold">{{ $this->getStats()['todayCount'] }} Termine</span>
                </div>
                <div>
                    <span class="text-gray-500">Umsatz:</span>
                    <span class="font-semibold text-green-600">€{{ number_format($this->getStats()['todayRevenue'], 2, ',', '.') }}</span>
                </div>
            </div>
        </x-slot>
        
        <div class="space-y-4">
            {{-- Mini Stats --}}
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-3">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium text-blue-600 dark:text-blue-400">Heute</p>
                            <p class="text-2xl font-bold text-blue-900 dark:text-blue-100">{{ $this->getStats()['todayCount'] }}</p>
                        </div>
                        <x-heroicon-o-calendar class="w-8 h-8 text-blue-500 opacity-50" />
                    </div>
                </div>
                
                <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-3">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium text-green-600 dark:text-green-400">Umsatz heute</p>
                            <p class="text-2xl font-bold text-green-900 dark:text-green-100">€{{ number_format($this->getStats()['todayRevenue'], 0, ',', '.') }}</p>
                        </div>
                        <x-heroicon-o-currency-euro class="w-8 h-8 text-green-500 opacity-50" />
                    </div>
                </div>
                
                <div class="bg-indigo-50 dark:bg-indigo-900/20 rounded-lg p-3">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium text-indigo-600 dark:text-indigo-400">Diese Woche</p>
                            <p class="text-2xl font-bold text-indigo-900 dark:text-indigo-100">{{ $this->getStats()['weekCount'] }}</p>
                        </div>
                        <x-heroicon-o-chart-bar class="w-8 h-8 text-indigo-500 opacity-50" />
                    </div>
                </div>
                
                <div class="bg-amber-50 dark:bg-amber-900/20 rounded-lg p-3">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium text-amber-600 dark:text-amber-400">Wochenumsatz</p>
                            <p class="text-2xl font-bold text-amber-900 dark:text-amber-100">€{{ number_format($this->getStats()['weekRevenue'], 0, ',', '.') }}</p>
                        </div>
                        <x-heroicon-o-banknotes class="w-8 h-8 text-amber-500 opacity-50" />
                    </div>
                </div>
            </div>
            
            {{-- Next Appointment Alert --}}
            @if($nextAppointment = $this->getStats()['nextAppointment'])
                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-3">
                    <div class="flex items-center">
                        <x-heroicon-o-clock class="w-5 h-5 text-blue-600 dark:text-blue-400 mr-2" />
                        <div class="flex-1">
                            <p class="text-sm font-medium text-blue-900 dark:text-blue-100">
                                Nächster Termin in {{ $nextAppointment->starts_at->diffForHumans() }}
                            </p>
                            <p class="text-sm text-blue-700 dark:text-blue-300">
                                {{ $nextAppointment->starts_at->format('H:i') }} - {{ $nextAppointment->customer->name }} ({{ $nextAppointment->service->name }})
                            </p>
                        </div>
                    </div>
                </div>
            @endif
            
            {{-- Today's Appointments Timeline --}}
            <div class="space-y-2">
                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Tagesübersicht</h4>
                
                @forelse($this->getAppointments() as $appointment)
                    <div class="relative flex items-center space-x-3 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 hover:border-gray-300 dark:hover:border-gray-600 transition-colors">
                        <div class="flex-shrink-0">
                            <span class="inline-flex h-10 w-10 items-center justify-center rounded-full {{ match($appointment['status']) {
                                'confirmed' => 'bg-green-100 dark:bg-green-900/30',
                                'pending' => 'bg-amber-100 dark:bg-amber-900/30',
                                'completed' => 'bg-gray-100 dark:bg-gray-700',
                                'cancelled' => 'bg-red-100 dark:bg-red-900/30',
                                default => 'bg-blue-100 dark:bg-blue-900/30'
                            } }}">
                                <span class="text-sm font-medium {{ match($appointment['status']) {
                                    'confirmed' => 'text-green-700 dark:text-green-300',
                                    'pending' => 'text-amber-700 dark:text-amber-300',
                                    'completed' => 'text-gray-700 dark:text-gray-300',
                                    'cancelled' => 'text-red-700 dark:text-red-300',
                                    default => 'text-blue-700 dark:text-blue-300'
                                } }}">{{ $appointment['time'] }}</span>
                            </span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <a href="{{ \App\Filament\Admin\Resources\AppointmentResource::getUrl('edit', ['record' => $appointment['id']]) }}" class="focus:outline-none">
                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                    {{ $appointment['customer'] }}
                                </p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ $appointment['service'] }} • {{ $appointment['staff'] }}
                                </p>
                            </a>
                        </div>
                        <div class="flex-shrink-0 text-right">
                            <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                €{{ number_format($appointment['revenue'], 2, ',', '.') }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ match($appointment['status']) {
                                    'confirmed' => 'Bestätigt',
                                    'pending' => 'Ausstehend',
                                    'completed' => 'Abgeschlossen',
                                    'cancelled' => 'Abgesagt',
                                    default => $appointment['status']
                                } }}
                            </p>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-6 text-gray-500 dark:text-gray-400">
                        <x-heroicon-o-calendar-days class="mx-auto h-12 w-12 text-gray-400" />
                        <p class="mt-2 text-sm">Keine Termine für heute</p>
                    </div>
                @endforelse
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>