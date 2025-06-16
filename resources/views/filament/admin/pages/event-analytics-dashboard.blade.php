<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Filter Form -->
        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm">
            {{ $this->form }}
        </div>
        
        @if($companyId)
            <!-- Haupt-Statistiken -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Gesamt-Termine -->
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Gesamt-Termine</p>
                            <p class="text-3xl font-bold text-gray-900 dark:text-gray-100">
                                {{ number_format($stats['total_appointments'] ?? 0) }}
                            </p>
                            <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">
                                <span class="text-green-600">{{ $stats['completion_rate'] ?? 0 }}%</span> abgeschlossen
                            </p>
                        </div>
                        <div class="p-3 bg-blue-100 dark:bg-blue-900 rounded-lg">
                            <x-heroicon-o-calendar class="w-8 h-8 text-blue-600 dark:text-blue-400" />
                        </div>
                    </div>
                </div>
                
                <!-- Umsatz -->
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Umsatz</p>
                            <p class="text-3xl font-bold text-gray-900 dark:text-gray-100">
                                {{ number_format($stats['revenue'] ?? 0, 2, ',', '.') }} €
                            </p>
                            <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">
                                Ø {{ number_format(($stats['revenue'] ?? 0) / max($stats['completed'] ?? 1, 1), 2, ',', '.') }} € pro Termin
                            </p>
                        </div>
                        <div class="p-3 bg-green-100 dark:bg-green-900 rounded-lg">
                            <x-heroicon-o-currency-euro class="w-8 h-8 text-green-600 dark:text-green-400" />
                        </div>
                    </div>
                </div>
                
                <!-- Auslastung -->
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Auslastung</p>
                            <p class="text-3xl font-bold text-gray-900 dark:text-gray-100">
                                {{ $stats['utilization'] ?? 0 }}%
                            </p>
                            <div class="mt-2 w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                <div class="bg-blue-600 h-2 rounded-full" style="width: {{ min($stats['utilization'] ?? 0, 100) }}%"></div>
                            </div>
                        </div>
                        <div class="p-3 bg-purple-100 dark:bg-purple-900 rounded-lg">
                            <x-heroicon-o-chart-bar class="w-8 h-8 text-purple-600 dark:text-purple-400" />
                        </div>
                    </div>
                </div>
                
                <!-- No-Show Rate -->
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">No-Show Rate</p>
                            <p class="text-3xl font-bold {{ ($stats['no_show_rate'] ?? 0) > 10 ? 'text-red-600' : 'text-gray-900 dark:text-gray-100' }}">
                                {{ $stats['no_show_rate'] ?? 0 }}%
                            </p>
                            <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">
                                {{ $stats['no_show'] ?? 0 }} von {{ $stats['total_appointments'] ?? 0 }}
                            </p>
                        </div>
                        <div class="p-3 bg-red-100 dark:bg-red-900 rounded-lg">
                            <x-heroicon-o-x-circle class="w-8 h-8 text-red-600 dark:text-red-400" />
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Charts -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Termine Timeline -->
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Termine im Zeitverlauf</h3>
                    <canvas id="appointmentsChart" class="w-full" style="height: 300px;"></canvas>
                </div>
                
                <!-- Umsatz Timeline -->
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Umsatzentwicklung</h3>
                    <canvas id="revenueChart" class="w-full" style="height: 300px;"></canvas>
                </div>
            </div>
            
            <!-- Heatmap -->
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Auslastungs-Heatmap</h3>
                <div class="text-sm text-gray-600 dark:text-gray-400 mb-4">Termine nach Wochentag und Uhrzeit</div>
                <div id="heatmap" class="w-full" style="height: 400px;"></div>
            </div>
            
            <!-- Top Performer -->
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Top Performer</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead>
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Mitarbeiter
                                </th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Termine
                                </th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Abgeschlossen
                                </th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Erfolgsrate
                                </th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    No-Show Rate
                                </th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Ø Dauer
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($topPerformers as $performer)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                        {{ $performer->name }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-500 dark:text-gray-400">
                                        {{ $performer->total_appointments }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-500 dark:text-gray-400">
                                        {{ $performer->completed }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                            {{ $performer->completion_rate >= 90 ? 'bg-green-100 text-green-800' : 
                                               ($performer->completion_rate >= 75 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                            {{ $performer->completion_rate }}%
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                            {{ $performer->no_show_rate <= 5 ? 'bg-green-100 text-green-800' : 
                                               ($performer->no_show_rate <= 10 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                            {{ $performer->no_show_rate }}%
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-500 dark:text-gray-400">
                                        {{ round($performer->avg_duration_minutes ?? 0) }} Min
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Event Type Stats -->
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Event-Type Performance</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead>
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Event-Type
                                </th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Buchungen
                                </th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Abgeschlossen
                                </th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Preis
                                </th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Umsatz
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($eventTypeStats as $eventType)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                            {{ $eventType->name }}
                                        </div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ $eventType->duration_minutes }} Minuten
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-500 dark:text-gray-400">
                                        {{ $eventType->bookings }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-500 dark:text-gray-400">
                                        {{ $eventType->completed }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-500 dark:text-gray-400">
                                        {{ number_format($eventType->price ?? 0, 2, ',', '.') }} €
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-center font-semibold text-gray-900 dark:text-gray-100">
                                        {{ number_format($eventType->revenue ?? 0, 2, ',', '.') }} €
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <div class="text-center py-12">
                <x-heroicon-o-chart-bar class="mx-auto h-12 w-12 text-gray-400" />
                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">Keine Daten</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Bitte wählen Sie ein Unternehmen aus, um die Analytics anzuzeigen.
                </p>
            </div>
        @endif
    </div>
    
    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            @if($companyId)
                // Appointments Chart
                const appointmentsCtx = document.getElementById('appointmentsChart').getContext('2d');
                new Chart(appointmentsCtx, {
                    type: 'bar',
                    data: @json($chartData['appointments_timeline'] ?? []),
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            x: { stacked: true },
                            y: { stacked: true }
                        }
                    }
                });
                
                // Revenue Chart
                const revenueCtx = document.getElementById('revenueChart').getContext('2d');
                new Chart(revenueCtx, {
                    type: 'line',
                    data: @json($chartData['revenue_timeline'] ?? []),
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false }
                        }
                    }
                });
                
                // Heatmap
                const heatmapOptions = {
                    series: [{
                        name: 'Termine',
                        data: @json($heatmapData ?? [])
                    }],
                    chart: {
                        height: 350,
                        type: 'heatmap',
                    },
                    dataLabels: {
                        enabled: false
                    },
                    colors: ["#008FFB"],
                    xaxis: {
                        type: 'category',
                        categories: ['00','01','02','03','04','05','06','07','08','09','10','11',
                                    '12','13','14','15','16','17','18','19','20','21','22','23']
                    },
                    yaxis: {
                        categories: ['So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa']
                    }
                };
                
                const heatmap = new ApexCharts(document.querySelector("#heatmap"), heatmapOptions);
                heatmap.render();
            @endif
        });
    </script>
    @endpush
</x-filament-panels::page>