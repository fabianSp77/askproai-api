<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Header Section - Clean Professional Design --}}
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">Dashboard</h1>
                    <p class="text-gray-600 dark:text-gray-400 mt-1">
                        Überblick über Ihre wichtigsten Kennzahlen und Aktivitäten
                    </p>
                </div>
                <div class="flex items-center space-x-4">
                    {{-- Status Indicator --}}
                    <div class="flex items-center space-x-2 px-4 py-2 bg-green-50 dark:bg-green-900/20 rounded-lg">
                        <div class="relative">
                            <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                            <div class="absolute inset-0 w-2 h-2 bg-green-500 rounded-full animate-ping"></div>
                        </div>
                        <span class="text-sm font-medium text-green-700 dark:text-green-300">System Online</span>
                    </div>
                    
                    {{-- Last Update --}}
                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        <span class="font-medium">Zuletzt aktualisiert:</span>
                        <span class="font-mono">{{ now()->format('H:i:s') }}</span>
                    </div>
                    
                    {{-- Refresh Button --}}
                    <button wire:click="refresh" 
                            class="p-2 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-colors"
                            title="Aktualisieren">
                        <x-heroicon-m-arrow-path class="w-5 h-5 text-gray-600 dark:text-gray-400" wire:loading.class="animate-spin" />
                    </button>
                </div>
            </div>
        </div>

        {{-- Key Metrics Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- Today's Appointments --}}
            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Heutige Termine</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white mt-1">
                            {{ $todayAppointments ?? 0 }}
                        </p>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                            <span class="text-green-600 dark:text-green-400">+{{ $appointmentsTrend ?? 0 }}%</span> vs. gestern
                        </p>
                    </div>
                    <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                        <x-heroicon-o-calendar class="w-8 h-8 text-blue-600 dark:text-blue-400" />
                    </div>
                </div>
            </div>
            
            {{-- Active Calls --}}
            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Aktive Anrufe</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white mt-1">
                            {{ $activeCalls ?? 0 }}
                        </p>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                            {{ $totalCallsToday ?? 0 }} heute gesamt
                        </p>
                    </div>
                    <div class="p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
                        <x-heroicon-o-phone class="w-8 h-8 text-green-600 dark:text-green-400" />
                    </div>
                </div>
            </div>
            
            {{-- Conversion Rate --}}
            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Konversionsrate</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white mt-1">
                            {{ $conversionRate ?? 0 }}%
                        </p>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                            Anrufe → Termine
                        </p>
                    </div>
                    <div class="p-3 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
                        <x-heroicon-o-chart-bar class="w-8 h-8 text-purple-600 dark:text-purple-400" />
                    </div>
                </div>
            </div>
            
            {{-- Revenue Today --}}
            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Heutiger Umsatz</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white mt-1">
                            {{ number_format($revenueToday ?? 0, 2, ',', '.') }} €
                        </p>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                            {{ $appointmentsCompleted ?? 0 }} abgeschlossen
                        </p>
                    </div>
                    <div class="p-3 bg-amber-50 dark:bg-amber-900/20 rounded-lg">
                        <x-heroicon-o-currency-euro class="w-8 h-8 text-amber-600 dark:text-amber-400" />
                    </div>
                </div>
            </div>
        </div>

        {{-- Performance Charts Row --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Call Volume Chart --}}
            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Anrufvolumen (7 Tage)</h3>
                    <button class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">
                        Details →
                    </button>
                </div>
                <div class="h-64 flex items-center justify-center text-gray-400">
                    {{-- Chart would go here --}}
                    <div class="text-center">
                        <x-heroicon-o-chart-bar class="w-12 h-12 mx-auto mb-2" />
                        <p class="text-sm">Diagramm wird geladen...</p>
                    </div>
                </div>
            </div>
            
            {{-- Appointment Status Distribution --}}
            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Terminstatus Verteilung</h3>
                    <button class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">
                        Details →
                    </button>
                </div>
                <div class="space-y-4">
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Geplant</span>
                            <span class="text-sm text-gray-600 dark:text-gray-400">{{ $scheduledCount ?? 0 }}</span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                            <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $scheduledPercentage ?? 0 }}%"></div>
                        </div>
                    </div>
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Abgeschlossen</span>
                            <span class="text-sm text-gray-600 dark:text-gray-400">{{ $completedCount ?? 0 }}</span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                            <div class="bg-green-600 h-2 rounded-full" style="width: {{ $completedPercentage ?? 0 }}%"></div>
                        </div>
                    </div>
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Abgesagt</span>
                            <span class="text-sm text-gray-600 dark:text-gray-400">{{ $cancelledCount ?? 0 }}</span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                            <div class="bg-red-600 h-2 rounded-full" style="width: {{ $cancelledPercentage ?? 0 }}%"></div>
                        </div>
                    </div>
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">No-Show</span>
                            <span class="text-sm text-gray-600 dark:text-gray-400">{{ $noShowCount ?? 0 }}</span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                            <div class="bg-amber-600 h-2 rounded-full" style="width: {{ $noShowPercentage ?? 0 }}%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Recent Activity & Quick Actions --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Recent Activity --}}
            <div class="lg:col-span-2 bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800">
                <div class="p-6 border-b border-gray-200 dark:border-gray-800">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Aktuelle Aktivitäten</h3>
                </div>
                <div class="divide-y divide-gray-200 dark:divide-gray-800">
                    @forelse($recentActivities ?? [] as $activity)
                        <div class="p-4 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                            <div class="flex items-start space-x-3">
                                <div class="p-2 bg-gray-100 dark:bg-gray-800 rounded-lg">
                                    @if($activity->type === 'appointment')
                                        <x-heroicon-o-calendar class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                                    @elseif($activity->type === 'call')
                                        <x-heroicon-o-phone class="w-5 h-5 text-green-600 dark:text-green-400" />
                                    @else
                                        <x-heroicon-o-user class="w-5 h-5 text-purple-600 dark:text-purple-400" />
                                    @endif
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $activity->description }}
                                    </p>
                                    <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                        {{ $activity->created_at->diffForHumans() }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="p-8 text-center text-gray-500 dark:text-gray-400">
                            <x-heroicon-o-clock class="w-12 h-12 mx-auto mb-3" />
                            <p>Keine aktuellen Aktivitäten</p>
                        </div>
                    @endforelse
                </div>
            </div>
            
            {{-- Quick Actions --}}
            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Schnellaktionen</h3>
                <div class="space-y-3">
                    <a href="{{ route('filament.admin.resources.appointments.create') }}" 
                       class="flex items-center space-x-3 p-3 hover:bg-gray-50 dark:hover:bg-gray-800 rounded-lg transition-colors">
                        <x-heroicon-o-plus-circle class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Neuer Termin</span>
                    </a>
                    <a href="{{ route('filament.admin.resources.customers.create') }}" 
                       class="flex items-center space-x-3 p-3 hover:bg-gray-50 dark:hover:bg-gray-800 rounded-lg transition-colors">
                        <x-heroicon-o-user-plus class="w-5 h-5 text-green-600 dark:text-green-400" />
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Neuer Kunde</span>
                    </a>
                    <a href="{{ route('filament.admin.resources.calls.index') }}" 
                       class="flex items-center space-x-3 p-3 hover:bg-gray-50 dark:hover:bg-gray-800 rounded-lg transition-colors">
                        <x-heroicon-o-phone-arrow-down-left class="w-5 h-5 text-purple-600 dark:text-purple-400" />
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Anrufe abrufen</span>
                    </a>
                    <div class="pt-3 border-t border-gray-200 dark:border-gray-800">
                        <a href="{{ route('filament.admin.pages.company-integration-portal') }}" 
                           class="flex items-center space-x-3 p-3 hover:bg-gray-50 dark:hover:bg-gray-800 rounded-lg transition-colors">
                            <x-heroicon-o-cog-6-tooth class="w-5 h-5 text-gray-600 dark:text-gray-400" />
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Integrationen</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- System Health Summary --}}
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">System Status</h3>
                <span class="text-sm text-gray-600 dark:text-gray-400">Alle Systeme</span>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="flex items-center space-x-3">
                    <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                    <span class="text-sm text-gray-700 dark:text-gray-300">API Gateway</span>
                </div>
                <div class="flex items-center space-x-3">
                    <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                    <span class="text-sm text-gray-700 dark:text-gray-300">Cal.com Integration</span>
                </div>
                <div class="flex items-center space-x-3">
                    <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                    <span class="text-sm text-gray-700 dark:text-gray-300">Retell.ai Integration</span>
                </div>
                <div class="flex items-center space-x-3">
                    <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                    <span class="text-sm text-gray-700 dark:text-gray-300">Datenbank</span>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            // Auto-refresh functionality
            let refreshInterval = 30; // seconds
            let countdown = refreshInterval;
            
            setInterval(() => {
                countdown--;
                if (countdown <= 0) {
                    countdown = refreshInterval;
                    // Trigger Livewire refresh
                    @this.refresh();
                }
            }, 1000);
        </script>
    @endpush
</x-filament-panels::page>