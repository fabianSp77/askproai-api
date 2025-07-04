<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Current Period Overview --}}
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg leading-6 font-medium text-gray-900">
                            Current Billing Period
                        </h3>
                        @if($currentPeriod)
                            <p class="mt-1 text-sm text-gray-500">
                                {{ $currentPeriod->start_date->format('M d') }} - {{ $currentPeriod->end_date->format('M d, Y') }}
                            </p>
                        @endif
                    </div>
                    <div class="flex space-x-3">
                        <button
                            wire:click="refreshData"
                            class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                        >
                            <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            Refresh
                        </button>
                    </div>
                </div>

                {{-- Usage Stats --}}
                <div class="mt-5 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="bg-gray-50 overflow-hidden rounded-lg px-4 py-5">
                        <dt class="text-sm font-medium text-gray-500 truncate">Total Calls</dt>
                        <dd class="mt-1 text-3xl font-semibold text-gray-900">
                            {{ number_format($currentUsage['total_calls'] ?? 0) }}
                        </dd>
                    </div>
                    
                    <div class="bg-gray-50 overflow-hidden rounded-lg px-4 py-5">
                        <dt class="text-sm font-medium text-gray-500 truncate">Minutes Used</dt>
                        <dd class="mt-1 text-3xl font-semibold text-gray-900">
                            {{ number_format($currentUsage['total_minutes'] ?? 0, 1) }}
                        </dd>
                        @if($currentPeriod && $currentPeriod->included_minutes > 0)
                            <p class="mt-1 text-sm text-gray-500">
                                of {{ number_format($currentPeriod->included_minutes) }} included
                            </p>
                        @endif
                    </div>
                    
                    <div class="bg-gray-50 overflow-hidden rounded-lg px-4 py-5">
                        <dt class="text-sm font-medium text-gray-500 truncate">Appointments</dt>
                        <dd class="mt-1 text-3xl font-semibold text-gray-900">
                            {{ number_format($currentUsage['appointments'] ?? 0) }}
                        </dd>
                    </div>
                    
                    <div class="bg-gray-50 overflow-hidden rounded-lg px-4 py-5">
                        <dt class="text-sm font-medium text-gray-500 truncate">Current Charges</dt>
                        <dd class="mt-1 text-3xl font-semibold text-gray-900">
                            €{{ number_format($currentUsage['total_cost'] ?? 0, 2) }}
                        </dd>
                    </div>
                </div>
            </div>
        </div>

        {{-- Usage Chart --}}
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                    Usage Trends
                </h3>
                <div style="height: 300px;">
                    <canvas id="usageChart"></canvas>
                </div>
            </div>
        </div>

        {{-- Billing History --}}
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                    Billing History
                </h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Period
                                </th>
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Minutes
                                </th>
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Appointments
                                </th>
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Total
                                </th>
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Status
                                </th>
                                <th class="px-6 py-3 bg-gray-50"></th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($billingHistory as $period)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $period['start_date']->format('M Y') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ number_format($period['total_minutes'], 1) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ number_format($period['appointment_count']) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        €{{ number_format($period['total_cost'], 2) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                            @if($period['status'] === 'paid') bg-green-100 text-green-800
                                            @elseif($period['status'] === 'pending') bg-yellow-100 text-yellow-800
                                            @else bg-gray-100 text-gray-800
                                            @endif">
                                            {{ ucfirst($period['status']) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        @if($period['invoice_id'])
                                            <a href="{{ route('invoice.download', $period['invoice_id']) }}" class="text-primary-600 hover:text-primary-900">
                                                Download
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">
                                        No billing history available
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Upcoming Charges --}}
        @if(count($upcomingCharges) > 0)
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                    Upcoming Charges
                </h3>
                <div class="space-y-3">
                    @foreach($upcomingCharges as $charge)
                        <div class="flex items-center justify-between py-3 border-b border-gray-200">
                            <div>
                                <p class="text-sm font-medium text-gray-900">
                                    {{ $charge['description'] }}
                                </p>
                                <p class="text-sm text-gray-500">
                                    Due {{ $charge['date'] }}
                                </p>
                            </div>
                            <div class="text-sm font-medium text-gray-900">
                                €{{ number_format($charge['amount'], 2) }}
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('usageChart').getContext('2d');
            const usageTrends = @json($usageTrends);
            
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: usageTrends.map(item => item.month),
                    datasets: [{
                        label: 'Minutes',
                        data: usageTrends.map(item => item.minutes),
                        borderColor: 'rgb(59, 130, 246)',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        yAxisID: 'y-minutes',
                    }, {
                        label: 'Appointments',
                        data: usageTrends.map(item => item.appointments),
                        borderColor: 'rgb(16, 185, 129)',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        yAxisID: 'y-appointments',
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        'y-minutes': {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            title: {
                                display: true,
                                text: 'Minutes'
                            }
                        },
                        'y-appointments': {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            title: {
                                display: true,
                                text: 'Appointments'
                            },
                            grid: {
                                drawOnChartArea: false,
                            },
                        }
                    }
                }
            });
        });
    </script>
    @endpush
</x-filament-panels::page>