@extends('portal.layouts.unified')

@section('page-title', 'Analysen')

@section('header-actions')
<form method="GET" action="{{ route('business.analytics.index') }}" class="flex items-center space-x-4">
    <div class="flex items-center space-x-2">
        <label for="start_date" class="text-sm text-gray-600">Von:</label>
        <input type="date" 
               name="start_date" 
               id="start_date"
               value="{{ $startDate->format('Y-m-d') }}"
               class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
    </div>
    <div class="flex items-center space-x-2">
        <label for="end_date" class="text-sm text-gray-600">Bis:</label>
        <input type="date" 
               name="end_date" 
               id="end_date"
               value="{{ $endDate->format('Y-m-d') }}"
               class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
    </div>
    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition duration-200 text-sm">
        <i class="fas fa-filter mr-2"></i>
        Filtern
    </button>
    <a href="{{ route('business.analytics.export') }}" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition duration-200 text-sm">
        <i class="fas fa-download mr-2"></i>
        Exportieren
    </a>
</form>
@endsection

@section('content')
<div class="p-6">
    <!-- Overview Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Anrufe gesamt</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">{{ number_format($overviewStats['total_calls']) }}</p>
                </div>
                <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-phone text-blue-600"></i>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Termine gesamt</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">{{ number_format($overviewStats['total_appointments']) }}</p>
                </div>
                <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-calendar-check text-green-600"></i>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Neue Kunden</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">{{ number_format($overviewStats['new_customers']) }}</p>
                </div>
                <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-user-plus text-purple-600"></i>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Konversionsrate</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">{{ $overviewStats['conversion_rate'] }}%</p>
                </div>
                <div class="w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-percentage text-yellow-600"></i>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Charts Row 1 -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Daily Call Volume -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Anrufvolumen (letzte 7 Tage)</h3>
            <canvas id="dailyCallsChart" height="150"></canvas>
        </div>
        
        <!-- Appointment Status Distribution -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Terminstatus-Verteilung</h3>
            <canvas id="appointmentStatusChart" height="150"></canvas>
        </div>
    </div>
    
    <!-- Charts Row 2 -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Top Services -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Top Dienstleistungen</h3>
            <div class="space-y-3">
                @forelse($topServices as $service)
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-700">{{ $service->name }}</span>
                    <div class="flex items-center">
                        <div class="w-32 bg-gray-200 rounded-full h-2 mr-2">
                            @php
                                $maxCount = $topServices->max('count');
                                $percentage = $maxCount > 0 ? ($service->count / $maxCount) * 100 : 0;
                            @endphp
                            <div class="bg-blue-600 rounded-full h-2" style="width: {{ $percentage }}%"></div>
                        </div>
                        <span class="text-sm font-medium text-gray-900 w-12 text-right">{{ $service->count }}</span>
                    </div>
                </div>
                @empty
                <p class="text-gray-500 text-sm">Keine Daten verfügbar</p>
                @endforelse
            </div>
        </div>
        
        <!-- Hourly Distribution -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Anrufe nach Uhrzeit</h3>
            <canvas id="hourlyDistributionChart" height="150"></canvas>
        </div>
    </div>
    
    <!-- Staff Performance Table -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Mitarbeiter-Performance</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mitarbeiter</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Termine gesamt</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Abgeschlossen</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Abgesagt</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Erfolgsquote</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($staffPerformance as $staff)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            {{ $staff->name }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $staff->total_appointments }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $staff->completed }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $staff->cancelled }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            @php
                                $successRate = $staff->total_appointments > 0 
                                    ? round(($staff->completed / $staff->total_appointments) * 100, 1) 
                                    : 0;
                            @endphp
                            <div class="flex items-center">
                                <div class="w-24 bg-gray-200 rounded-full h-2 mr-2">
                                    <div class="bg-green-600 rounded-full h-2" style="width: {{ $successRate }}%"></div>
                                </div>
                                <span>{{ $successRate }}%</span>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                            Keine Daten verfügbar
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Branch Performance (if available) -->
    @if($branchPerformance)
    <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Filial-Performance</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Filiale</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Termine</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Einzelkunden</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($branchPerformance as $branch)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            {{ $branch->name }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $branch->total_appointments }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $branch->unique_customers }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Daily Calls Chart
    const dailyCallsCtx = document.getElementById('dailyCallsChart').getContext('2d');
    new Chart(dailyCallsCtx, {
        type: 'line',
        data: {
            labels: {!! json_encode($dailyCalls->pluck('date')) !!},
            datasets: [{
                label: 'Anrufe',
                data: {!! json_encode($dailyCalls->pluck('count')) !!},
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
    
    // Appointment Status Chart
    const appointmentStatusCtx = document.getElementById('appointmentStatusChart').getContext('2d');
    const statusLabels = {
        'scheduled': 'Geplant',
        'confirmed': 'Bestätigt',
        'completed': 'Abgeschlossen',
        'cancelled': 'Abgesagt',
        'no_show': 'Nicht erschienen'
    };
    
    const appointmentStatusData = @json($appointmentStatus);
    const labels = Object.keys(appointmentStatusData).map(k => statusLabels[k] || k);
    const data = Object.values(appointmentStatusData);
    
    new Chart(appointmentStatusCtx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: data,
                backgroundColor: [
                    'rgb(59, 130, 246)',
                    'rgb(34, 197, 94)',
                    'rgb(168, 85, 247)',
                    'rgb(239, 68, 68)',
                    'rgb(245, 158, 11)'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                }
            }
        }
    });
    
    // Hourly Distribution Chart
    const hourlyCtx = document.getElementById('hourlyDistributionChart').getContext('2d');
    new Chart(hourlyCtx, {
        type: 'bar',
        data: {
            labels: {!! json_encode($hourlyDistribution->pluck('hour')) !!},
            datasets: [{
                label: 'Anrufe',
                data: {!! json_encode($hourlyDistribution->pluck('count')) !!},
                backgroundColor: 'rgba(59, 130, 246, 0.8)'
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
</script>
@endpush
@endsection