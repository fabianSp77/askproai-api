<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Filter Section --}}
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Filter</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Von</label>
                    <input type="date" wire:model.live="dateFrom" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                </div>
                
                <div>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Bis</label>
                    <input type="date" wire:model.live="dateTo" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                </div>
                
                <div>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Filiale</label>
                    <select wire:model.live="selectedBranch" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        <option value="all">Alle Filialen</option>
                        @foreach($branches as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Berichtstyp</label>
                    <select wire:model.live="reportType" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        <option value="overview">Übersicht</option>
                        <option value="appointments">Termine</option>
                        <option value="calls">Anrufe</option>
                        <option value="customers">Kunden</option>
                    </select>
                </div>
            </div>
            
            <div class="mt-4 flex justify-end">
                <x-filament::button wire:click="exportReport" icon="heroicon-o-arrow-down-tray">
                    Bericht exportieren
                </x-filament::button>
            </div>
        </div>
        
        {{-- Stats Overview --}}
        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
            <x-filament::card>
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Termine gesamt</div>
                <div class="mt-1 text-2xl font-semibold text-gray-900 dark:text-white">{{ number_format($stats['total_appointments']) }}</div>
            </x-filament::card>
            
            <x-filament::card>
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Abgeschlossen</div>
                <div class="mt-1 text-2xl font-semibold text-green-600">{{ number_format($stats['completed_appointments']) }}</div>
            </x-filament::card>
            
            <x-filament::card>
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Abgesagt</div>
                <div class="mt-1 text-2xl font-semibold text-red-600">{{ number_format($stats['cancelled_appointments']) }}</div>
            </x-filament::card>
            
            <x-filament::card>
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Anrufe gesamt</div>
                <div class="mt-1 text-2xl font-semibold text-gray-900 dark:text-white">{{ number_format($stats['total_calls']) }}</div>
            </x-filament::card>
            
            <x-filament::card>
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Neue Kunden</div>
                <div class="mt-1 text-2xl font-semibold text-blue-600">{{ number_format($stats['new_customers']) }}</div>
            </x-filament::card>
            
            <x-filament::card>
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Konversionsrate</div>
                <div class="mt-1 text-2xl font-semibold text-purple-600">{{ $stats['conversion_rate'] }}%</div>
            </x-filament::card>
        </div>
        
        {{-- Chart Section --}}
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Verlaufsdiagramm</h3>
            
            <div class="h-96">
                <canvas id="reportChart"></canvas>
            </div>
        </div>
        
        {{-- Additional Report Sections based on reportType --}}
        @if($reportType === 'appointments')
            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Termin-Details</h3>
                {{-- TODO: Add detailed appointment analytics --}}
                <p class="text-gray-500">Detaillierte Terminanalyse wird hier angezeigt...</p>
            </div>
        @elseif($reportType === 'calls')
            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Anruf-Details</h3>
                {{-- TODO: Add detailed call analytics --}}
                <p class="text-gray-500">Detaillierte Anrufanalyse wird hier angezeigt...</p>
            </div>
        @elseif($reportType === 'customers')
            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Kunden-Details</h3>
                {{-- TODO: Add detailed customer analytics --}}
                <p class="text-gray-500">Detaillierte Kundenanalyse wird hier angezeigt...</p>
            </div>
        @endif
    </div>
    
    {{-- Chart.js Integration --}}
    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('reportChart').getContext('2d');
            const chartData = @json($chartData);
            
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: chartData.map(item => item.date),
                    datasets: [{
                        label: 'Termine',
                        data: chartData.map(item => item.appointments),
                        borderColor: 'rgb(59, 130, 246)',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        tension: 0.1
                    }, {
                        label: 'Anrufe',
                        data: chartData.map(item => item.calls),
                        borderColor: 'rgb(16, 185, 129)',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        tension: 0.1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        title: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        });
        
        // Refresh chart on filter change
        Livewire.on('refresh', () => {
            location.reload();
        });
    </script>
    @endpush
</x-filament-panels::page>