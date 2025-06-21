<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Customer Analytics
        </x-slot>

        <x-slot name="headerActions">
            <x-filament::input.wrapper>
                <x-filament::input.select wire:model.live="timeRange">
                    @foreach($this->getTimeRangeOptions() as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </x-filament::input.select>
            </x-filament::input.wrapper>
        </x-slot>

        <!-- Customer Overview Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <!-- Total Customers -->
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Customers</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">
                            {{ number_format($customerData['overview']['total'] ?? 0) }}
                        </p>
                    </div>
                    <x-heroicon-o-users class="w-8 h-8 text-blue-400" />
                </div>
                <p class="text-xs text-gray-500 mt-2">
                    {{ $customerData['overview']['active'] ?? 0 }} active
                </p>
            </div>

            <!-- New Customers -->
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">New Customers</p>
                        <p class="text-2xl font-bold text-green-600">
                            +{{ $customerData['overview']['new'] ?? 0 }}
                        </p>
                    </div>
                    <div class="flex items-center">
                        @if(($customerData['overview']['growth_direction'] ?? 'stable') === 'up')
                            <x-heroicon-m-arrow-trending-up class="w-6 h-6 text-green-500" />
                        @elseif(($customerData['overview']['growth_direction'] ?? 'stable') === 'down')
                            <x-heroicon-m-arrow-trending-down class="w-6 h-6 text-red-500" />
                        @else
                            <x-heroicon-m-minus class="w-6 h-6 text-gray-500" />
                        @endif
                        <span class="ml-1 text-sm font-medium {{ ($customerData['overview']['growth_direction'] ?? 'stable') === 'up' ? 'text-green-600' : (($customerData['overview']['growth_direction'] ?? 'stable') === 'down' ? 'text-red-600' : 'text-gray-600') }}">
                            {{ abs($customerData['overview']['growth_rate'] ?? 0) }}%
                        </span>
                    </div>
                </div>
            </div>

            <!-- Retention Rate -->
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Retention Rate</p>
                        <p class="text-2xl font-bold {{ ($customerData['overview']['retention_rate'] ?? 0) >= 80 ? 'text-green-600' : (($customerData['overview']['retention_rate'] ?? 0) >= 60 ? 'text-yellow-600' : 'text-red-600') }}">
                            {{ $customerData['overview']['retention_rate'] ?? 0 }}%
                        </p>
                    </div>
                    <x-heroicon-o-arrow-path class="w-8 h-8 text-purple-400" />
                </div>
                <p class="text-xs text-gray-500 mt-2">
                    {{ $customerData['overview']['returning'] ?? 0 }} returning
                </p>
            </div>

            <!-- Avg Appointments -->
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Avg Appointments</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">
                            {{ $customerData['overview']['avg_appointments'] ?? 0 }}
                        </p>
                    </div>
                    <x-heroicon-o-calendar class="w-8 h-8 text-indigo-400" />
                </div>
                <p class="text-xs text-gray-500 mt-2">Per active customer</p>
            </div>
        </div>

        <!-- Customer Segments -->
        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700 mb-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Customer Segments</h3>
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="text-center">
                    <div class="text-3xl font-bold text-purple-600">{{ $customerData['segments']['vip'] ?? 0 }}</div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">VIP Customers</p>
                    <p class="text-xs text-gray-500">10+ visits</p>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-bold text-blue-600">{{ $customerData['segments']['regular'] ?? 0 }}</div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Regular</p>
                    <p class="text-xs text-gray-500">3-9 visits</p>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-bold text-gray-600">{{ $customerData['segments']['occasional'] ?? 0 }}</div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Occasional</p>
                    <p class="text-xs text-gray-500">1-2 visits</p>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-bold text-red-600">{{ $customerData['segments']['at_risk'] ?? 0 }}</div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">At Risk</p>
                    <p class="text-xs text-gray-500">60+ days inactive</p>
                </div>
            </div>
        </div>

        <!-- Lifetime Value Analysis -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- LTV Summary -->
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Lifetime Value Analysis</h3>
                <div class="mb-4">
                    <p class="text-sm text-gray-600 dark:text-gray-400">Average Customer LTV</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white">
                        €{{ number_format($customerData['lifetime_value']['average'] ?? 0, 2) }}
                    </p>
                </div>
                <div class="space-y-2">
                    @foreach(($customerData['lifetime_value']['distribution'] ?? []) as $range => $count)
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600 dark:text-gray-400">€{{ $range }}</span>
                            <div class="flex items-center">
                                <div class="w-32 bg-gray-200 rounded-full h-2 mr-2">
                                    @php
                                        $total = array_sum($customerData['lifetime_value']['distribution'] ?? []);
                                        $percentage = $total > 0 ? ($count / $total) * 100 : 0;
                                    @endphp
                                    <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $percentage }}%"></div>
                                </div>
                                <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $count }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Customer Acquisition Trend -->
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Customer Acquisition Trend</h3>
                <div class="h-48">
                    <canvas id="acquisitionChart" wire:ignore></canvas>
                </div>
            </div>
        </div>

        <!-- Top Customers Table -->
        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700 mb-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Top Customers</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-700">
                            <th class="text-left py-2 px-4">Customer</th>
                            <th class="text-center py-2 px-4">Appointments</th>
                            <th class="text-right py-2 px-4">Total Revenue</th>
                            <th class="text-right py-2 px-4">Avg Revenue</th>
                            <th class="text-right py-2 px-4">Last Visit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach(($customerData['top_customers'] ?? []) as $customer)
                            <tr class="border-b border-gray-100 dark:border-gray-800">
                                <td class="py-2 px-4">
                                    <div>
                                        <p class="font-medium text-gray-900 dark:text-white">{{ $customer['name'] }}</p>
                                        <p class="text-xs text-gray-500">{{ $customer['email'] }}</p>
                                    </div>
                                </td>
                                <td class="text-center py-2 px-4">{{ $customer['appointments'] }}</td>
                                <td class="text-right py-2 px-4 font-medium">€{{ number_format($customer['total_revenue'], 2) }}</td>
                                <td class="text-right py-2 px-4">€{{ number_format($customer['avg_revenue'], 2) }}</td>
                                <td class="text-right py-2 px-4 text-sm">{{ $customer['last_visit'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Churn Risk Customers -->
        @if(count($customerData['churn_risk'] ?? []) > 0)
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    Customers at Risk of Churning
                    <span class="text-sm font-normal text-gray-500">(No activity in 60-90 days)</span>
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach(array_slice($customerData['churn_risk'] ?? [], 0, 6) as $customer)
                        <div class="p-3 border rounded-lg {{ $customer['risk_level'] === 'high' ? 'border-red-300 bg-red-50 dark:bg-red-900/20' : 'border-yellow-300 bg-yellow-50 dark:bg-yellow-900/20' }}">
                            <div class="flex justify-between items-start">
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-white">{{ $customer['name'] }}</p>
                                    <p class="text-xs text-gray-500">{{ $customer['total_appointments'] }} total visits</p>
                                    <p class="text-xs text-gray-500">Last: {{ $customer['last_visit'] }}</p>
                                </div>
                                <span class="text-xs px-2 py-1 rounded {{ $customer['risk_level'] === 'high' ? 'bg-red-200 text-red-800' : 'bg-yellow-200 text-yellow-800' }}">
                                    {{ $customer['days_inactive'] }} days
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </x-filament::section>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('livewire:initialized', () => {
            const ctx = document.getElementById('acquisitionChart').getContext('2d');
            const acquisitionData = @json($customerData['acquisition'] ?? []);
            
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: acquisitionData.map(item => item.period),
                    datasets: [{
                        label: 'New Customers',
                        data: acquisitionData.map(item => item.new_customers),
                        backgroundColor: 'rgba(34, 197, 94, 0.5)',
                        borderColor: 'rgb(34, 197, 94)',
                        borderWidth: 1
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
                                stepSize: 1
                            }
                        }
                    }
                }
            });
        });
    </script>
    @endpush
</x-filament-widgets::widget>