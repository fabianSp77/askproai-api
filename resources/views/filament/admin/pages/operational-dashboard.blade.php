<x-filament-panels::page>
    {{-- Phone Agent Status Widget --}}
    <div class="mb-6">
        @livewire(\App\Filament\Admin\Widgets\PhoneAgentStatusWidget::class)
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <!-- Appointments Today -->
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                Termine heute
                            </dt>
                            <dd class="flex items-baseline">
                                <div class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                                    {{ $todayAppointments }}
                                </div>
                                @if($appointmentsTrend != 0)
                                    <p class="ml-2 flex items-baseline text-sm font-semibold {{ $appointmentsTrend > 0 ? 'text-green-600' : 'text-red-600' }}">
                                        {{ $appointmentsTrend > 0 ? '+' : '' }}{{ $appointmentsTrend }}%
                                    </p>
                                @endif
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <!-- Active Calls -->
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                Aktive Anrufe
                            </dt>
                            <dd>
                                <div class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                                    {{ $activeCalls }}
                                </div>
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Calls Today -->
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                Anrufe heute
                            </dt>
                            <dd>
                                <div class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                                    {{ $totalCallsToday }}
                                </div>
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <!-- Conversion Rate -->
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                Konversionsrate
                            </dt>
                            <dd>
                                <div class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                                    {{ $conversionRate }}%
                                </div>
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Call Volume by Hour -->
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Anrufvolumen nach Stunde</h3>
            @if(count($callVolumeByHour) > 0)
                <div class="h-64">
                    <canvas id="callVolumeChart"></canvas>
                </div>
            @else
                <p class="text-gray-500 dark:text-gray-400">Keine Daten verfügbar</p>
            @endif
        </div>

        <!-- Appointment Status Distribution -->
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Termin-Status Verteilung</h3>
            @if(count($appointmentStatusDistribution) > 0)
                <div class="h-64">
                    <canvas id="statusChart"></canvas>
                </div>
            @else
                <p class="text-gray-500 dark:text-gray-400">Keine Daten verfügbar</p>
            @endif
        </div>
    </div>

    <!-- Top Services -->
    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Top Services</h3>
        @if(count($topServices) > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead>
                        <tr>
                            <th class="px-6 py-3 bg-gray-50 dark:bg-gray-700 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Service
                            </th>
                            <th class="px-6 py-3 bg-gray-50 dark:bg-gray-700 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Buchungen
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($topServices as $service)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                    {{ $service->name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100 text-right">
                                    {{ $service->count }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-gray-500 dark:text-gray-400">Keine Daten verfügbar</p>
        @endif
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.2.1/dist/chart.umd.min.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Call Volume Chart
                @if(count($callVolumeByHour) > 0)
                    const callVolumeCtx = document.getElementById('callVolumeChart').getContext('2d');
                    new Chart(callVolumeCtx, {
                        type: 'line',
                        data: {
                            labels: {!! json_encode(array_keys($callVolumeByHour)) !!},
                            datasets: [{
                                label: 'Anrufe',
                                data: {!! json_encode(array_values($callVolumeByHour)) !!},
                                borderColor: 'rgb(59, 130, 246)',
                                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                                tension: 0.4
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: false
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        precision: 0
                                    }
                                }
                            }
                        }
                    });
                @endif

                // Status Distribution Chart
                @if(count($appointmentStatusDistribution) > 0)
                    const statusCtx = document.getElementById('statusChart').getContext('2d');
                    new Chart(statusCtx, {
                        type: 'doughnut',
                        data: {
                            labels: {!! json_encode(array_keys($appointmentStatusDistribution)) !!},
                            datasets: [{
                                data: {!! json_encode(array_values($appointmentStatusDistribution)) !!},
                                backgroundColor: [
                                    'rgb(34, 197, 94)',
                                    'rgb(251, 191, 36)',
                                    'rgb(239, 68, 68)',
                                    'rgb(156, 163, 175)'
                                ]
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'bottom'
                                }
                            }
                        }
                    });
                @endif

                // Refresh button
                const refreshButton = document.querySelector('[wire\\:click="refreshData"]');
                if (refreshButton) {
                    refreshButton.addEventListener('click', function() {
                        window.location.reload();
                    });
                }
            });
        </script>
    @endpush
</x-filament-panels::page>