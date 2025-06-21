<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Branch Performance Comparison
        </x-slot>

        <x-slot name="headerActions">
            <x-filament::input.wrapper>
                <x-filament::input.select wire:model.live="timeframe">
                    @foreach($this->getTimeframeOptions() as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </x-filament::input.select>
            </x-filament::input.wrapper>
        </x-slot>

        @if(empty($comparisonData))
            <div class="text-center py-8 text-gray-500">
                No branch data available for the selected timeframe.
            </div>
        @else
            <div class="grid grid-cols-1 gap-4">
                @foreach($comparisonData as $branch)
                    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                        <!-- Branch Header -->
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                    {{ $branch['branch_name'] }}
                                </h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $branch['city'] }}</p>
                            </div>
                            <div class="text-right">
                                <div class="text-2xl font-bold {{ $branch['performance_score'] >= 80 ? 'text-green-600' : ($branch['performance_score'] >= 60 ? 'text-yellow-600' : 'text-red-600') }}">
                                    {{ $branch['performance_score'] }}%
                                </div>
                                <p class="text-xs text-gray-500">Performance Score</p>
                            </div>
                        </div>

                        <!-- Metrics Grid -->
                        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                            <!-- Appointments -->
                            <div class="text-center">
                                <div class="text-2xl font-semibold text-gray-900 dark:text-white">
                                    {{ $branch['metrics']['appointments']['total'] }}
                                </div>
                                <p class="text-xs text-gray-500">Total Appointments</p>
                                <div class="flex items-center justify-center mt-1">
                                    @if($branch['trend']['appointments']['direction'] === 'up')
                                        <x-heroicon-m-arrow-trending-up class="w-4 h-4 text-green-500 mr-1" />
                                    @elseif($branch['trend']['appointments']['direction'] === 'down')
                                        <x-heroicon-m-arrow-trending-down class="w-4 h-4 text-red-500 mr-1" />
                                    @else
                                        <x-heroicon-m-minus class="w-4 h-4 text-gray-500 mr-1" />
                                    @endif
                                    <span class="text-xs {{ $branch['trend']['appointments']['direction'] === 'up' ? 'text-green-600' : ($branch['trend']['appointments']['direction'] === 'down' ? 'text-red-600' : 'text-gray-600') }}">
                                        {{ abs($branch['trend']['appointments']['value']) }}%
                                    </span>
                                </div>
                            </div>

                            <!-- Revenue -->
                            <div class="text-center">
                                <div class="text-2xl font-semibold text-gray-900 dark:text-white">
                                    €{{ number_format($branch['metrics']['financial']['revenue'], 2) }}
                                </div>
                                <p class="text-xs text-gray-500">Revenue</p>
                                <div class="flex items-center justify-center mt-1">
                                    @if($branch['trend']['revenue']['direction'] === 'up')
                                        <x-heroicon-m-arrow-trending-up class="w-4 h-4 text-green-500 mr-1" />
                                    @elseif($branch['trend']['revenue']['direction'] === 'down')
                                        <x-heroicon-m-arrow-trending-down class="w-4 h-4 text-red-500 mr-1" />
                                    @else
                                        <x-heroicon-m-minus class="w-4 h-4 text-gray-500 mr-1" />
                                    @endif
                                    <span class="text-xs {{ $branch['trend']['revenue']['direction'] === 'up' ? 'text-green-600' : ($branch['trend']['revenue']['direction'] === 'down' ? 'text-red-600' : 'text-gray-600') }}">
                                        {{ abs($branch['trend']['revenue']['value']) }}%
                                    </span>
                                </div>
                            </div>

                            <!-- Completion Rate -->
                            <div class="text-center">
                                <div class="text-2xl font-semibold {{ $branch['metrics']['rates']['completion'] >= 90 ? 'text-green-600' : ($branch['metrics']['rates']['completion'] >= 75 ? 'text-yellow-600' : 'text-red-600') }}">
                                    {{ $branch['metrics']['rates']['completion'] }}%
                                </div>
                                <p class="text-xs text-gray-500">Completion Rate</p>
                                <p class="text-xs text-gray-400 mt-1">
                                    {{ $branch['metrics']['appointments']['completed'] }} of {{ $branch['metrics']['appointments']['total'] }}
                                </p>
                            </div>

                            <!-- Utilization Rate -->
                            <div class="text-center">
                                <div class="text-2xl font-semibold {{ $branch['metrics']['rates']['utilization'] >= 80 ? 'text-green-600' : ($branch['metrics']['rates']['utilization'] >= 60 ? 'text-yellow-600' : 'text-red-600') }}">
                                    {{ $branch['metrics']['rates']['utilization'] }}%
                                </div>
                                <p class="text-xs text-gray-500">Utilization</p>
                                <p class="text-xs text-gray-400 mt-1">
                                    {{ $branch['metrics']['resources']['active_staff'] }} staff
                                </p>
                            </div>

                            <!-- Conversion Rate -->
                            <div class="text-center">
                                <div class="text-2xl font-semibold {{ $branch['metrics']['rates']['conversion'] >= 50 ? 'text-green-600' : ($branch['metrics']['rates']['conversion'] >= 30 ? 'text-yellow-600' : 'text-red-600') }}">
                                    {{ $branch['metrics']['rates']['conversion'] }}%
                                </div>
                                <p class="text-xs text-gray-500">Call Conversion</p>
                                <p class="text-xs text-gray-400 mt-1">
                                    {{ $branch['metrics']['resources']['total_calls'] }} calls
                                </p>
                            </div>

                            <!-- Customers -->
                            <div class="text-center">
                                <div class="text-2xl font-semibold text-gray-900 dark:text-white">
                                    {{ $branch['metrics']['customers']['unique'] }}
                                </div>
                                <p class="text-xs text-gray-500">Customers</p>
                                <p class="text-xs text-green-600 mt-1">
                                    +{{ $branch['metrics']['customers']['new'] }} new
                                </p>
                            </div>
                        </div>

                        <!-- Additional Metrics Bar -->
                        <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                            <div class="grid grid-cols-4 gap-4 text-center">
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">
                                        €{{ number_format($branch['metrics']['financial']['avg_per_appointment'], 2) }}
                                    </p>
                                    <p class="text-xs text-gray-500">Avg per Appointment</p>
                                </div>
                                <div>
                                    <p class="text-sm font-medium {{ $branch['metrics']['rates']['no_show'] <= 5 ? 'text-green-600' : ($branch['metrics']['rates']['no_show'] <= 10 ? 'text-yellow-600' : 'text-red-600') }}">
                                        {{ $branch['metrics']['rates']['no_show'] }}%
                                    </p>
                                    <p class="text-xs text-gray-500">No-Show Rate</p>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $branch['metrics']['appointments']['cancelled'] }}
                                    </p>
                                    <p class="text-xs text-gray-500">Cancelled</p>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $branch['metrics']['appointments']['no_show'] }}
                                    </p>
                                    <p class="text-xs text-gray-500">No-Shows</p>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>