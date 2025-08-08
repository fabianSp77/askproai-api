<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Filter Form -->
        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm">
            {{ $this->form }}
        </div>
        
        @if(!$companyId && auth()->user()->hasRole(['Super Admin', 'super_admin']))
            <!-- Gesamt-Übersicht für alle Unternehmen -->
            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-gray-800 dark:to-gray-900 rounded-xl p-6 shadow-sm">
                <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-4">
                    📊 Gesamt-Übersicht aller Unternehmen
                </h2>
                
                <!-- Hauptmetriken -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-4">
                        <div class="text-sm text-gray-500 dark:text-gray-400">Aktive Unternehmen</div>
                        <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $stats['total_companies'] ?? 0 }}</div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-4">
                        <div class="text-sm text-gray-500 dark:text-gray-400">Gesamt-Termine</div>
                        <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ number_format($stats['total_appointments'] ?? 0) }}</div>
                        <div class="text-xs text-green-600 mt-1">{{ $stats['completion_rate'] ?? 0 }}% abgeschlossen</div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-4">
                        <div class="text-sm text-gray-500 dark:text-gray-400">Gesamt-Anrufe</div>
                        <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ number_format($stats['total_calls'] ?? 0) }}</div>
                        <div class="text-xs text-blue-600 mt-1">{{ $stats['call_success_rate'] ?? 0 }}% erfolgreich</div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-4">
                        <div class="text-sm text-gray-500 dark:text-gray-400">Gesamt-Umsatz</div>
                        <div class="text-2xl font-bold text-green-600">{{ number_format($stats['revenue'] ?? 0, 2, ',', '.') }} €</div>
                        <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">Ø {{ number_format($stats['avg_revenue_per_appointment'] ?? 0, 2, ',', '.') }} €/Termin</div>
                    </div>
                </div>
            </div>
        @elseif($companyId)
            <!-- Company Selected - Show Stats and Charts -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Stats Cards -->
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Gesamt-Termine</p>
                            <p class="text-3xl font-bold text-gray-900 dark:text-gray-100">
                                {{ number_format($stats['total_appointments'] ?? 0) }}
                            </p>
                        </div>
                        <div class="p-3 bg-blue-100 dark:bg-blue-900 rounded-lg">
                            <x-heroicon-o-calendar class="w-8 h-8 text-blue-600 dark:text-blue-400" />
                        </div>
                    </div>
                </div>
                
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Umsatz</p>
                            <p class="text-3xl font-bold text-gray-900 dark:text-gray-100">
                                {{ number_format($stats['revenue'] ?? 0, 2, ',', '.') }} €
                            </p>
                        </div>
                        <div class="p-3 bg-green-100 dark:bg-green-900 rounded-lg">
                            <x-heroicon-o-currency-euro class="w-8 h-8 text-green-600 dark:text-green-400" />
                        </div>
                    </div>
                </div>
                
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Auslastung</p>
                            <p class="text-3xl font-bold text-gray-900 dark:text-gray-100">
                                {{ $stats['utilization'] ?? 0 }}%
                            </p>
                        </div>
                        <div class="p-3 bg-purple-100 dark:bg-purple-900 rounded-lg">
                            <x-heroicon-o-chart-bar class="w-8 h-8 text-purple-600 dark:text-purple-400" />
                        </div>
                    </div>
                </div>
                
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">No-Show Rate</p>
                            <p class="text-3xl font-bold text-gray-900 dark:text-gray-100">
                                {{ $stats['no_show_rate'] ?? 0 }}%
                            </p>
                        </div>
                        <div class="p-3 bg-red-100 dark:bg-red-900 rounded-lg">
                            <x-heroicon-o-x-circle class="w-8 h-8 text-red-600 dark:text-red-400" />
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Charts Container -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <!-- Appointments Chart -->
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Termine im Zeitverlauf</h3>
                    <canvas id="appointmentsChart" class="w-full" style="height: 300px;"></canvas>
                </div>
                
                <!-- Revenue Chart -->
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Umsatzentwicklung</h3>
                    <canvas id="revenueChart" class="w-full" style="height: 300px;"></canvas>
                </div>
            </div>
            
            <!-- Call Charts -->
            @if($viewMode === 'inbound' || $viewMode === 'combined')
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Anrufverteilung</h3>
                        <canvas id="callDistributionChart" class="w-full" style="height: 300px;"></canvas>
                    </div>
                    
                    <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Anrufe im Zeitverlauf</h3>
                        <canvas id="callsTimelineChart" class="w-full" style="height: 300px;"></canvas>
                    </div>
                </div>
            @endif
            
            <!-- Heatmap -->
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Termine Heatmap</h3>
                <div id="heatmap" class="w-full" style="height: 400px;"></div>
            </div>
        @else
            <!-- No Company Selected -->
            <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-xl p-6 text-center">
                <x-heroicon-o-exclamation-triangle class="w-12 h-12 text-yellow-600 dark:text-yellow-400 mx-auto mb-4" />
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">
                    Kein Unternehmen ausgewählt
                </h3>
                <p class="text-gray-600 dark:text-gray-300">
                    Bitte wählen Sie ein Unternehmen aus der Dropdown-Liste aus, um die Analytics anzuzeigen.
                </p>
            </div>
        @endif
    </div>
    
    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts@3.44.0/dist/apexcharts.min.js"></script>
    
    @if($companyId)
    <script>
        // Make data available globally
        window.analyticsChartData = @json($chartData ?? []);
        window.analyticsHeatmapData = @json($heatmapData ?? []);
        window.analyticsCallMetrics = @json($callMetrics ?? []);
        
        console.log('📊 Analytics data loaded for company:', {{ $companyId }});
        console.log('Chart data keys:', Object.keys(window.analyticsChartData));
    </script>
    
    <!-- Load the simple chart creator -->
    <script src="{{ asset('js/analytics-charts-simple.js') }}"></script>
    @endif
    @endpush
</x-filament-panels::page>