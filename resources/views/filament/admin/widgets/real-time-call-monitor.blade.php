<x-filament-widgets::widget class="fi-wi-real-time-monitor">
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                {{ static::$heading }}
                @if($hasActiveCalls)
                    <div class="flex items-center gap-1">
                        <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
                        <span class="text-xs text-green-600 dark:text-green-400">Live</span>
                    </div>
                @endif
            </div>
        </x-slot>

        <div class="space-y-6">
            {{-- Today's Stats --}}
            <div class="grid grid-cols-2 md:grid-cols-6 gap-3 pb-4 border-b border-gray-200 dark:border-gray-700">
                <div class="text-center">
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $todayStats['total'] }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">Total Calls</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $todayStats['active'] }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">Active Now</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $todayStats['completed'] }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">Completed</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-red-600 dark:text-red-400">{{ $todayStats['failed'] }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">Failed</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ $todayStats['success_rate'] }}%</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">Success Rate</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ gmdate('i:s', $todayStats['avg_duration']) }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">Avg Duration</div>
                </div>
            </div>

            {{-- Active Calls --}}
            @if($activeCalls->count() > 0)
                <div>
                    <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-2">
                        <x-heroicon-m-phone class="w-4 h-4 text-green-600 animate-pulse" />
                        Active Calls ({{ $activeCalls->count() }})
                    </h3>
                    <div class="space-y-2">
                        @foreach($activeCalls as $call)
                            <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2 mb-1">
                                            <span class="font-medium text-sm text-gray-900 dark:text-white">
                                                {{ $call['customer_name'] }}
                                            </span>
                                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ $call['to_number'] }}
                                            </span>
                                            @if($call['campaign'])
                                                <x-filament::badge size="xs" color="info">
                                                    {{ $call['campaign'] }}
                                                </x-filament::badge>
                                            @endif
                                        </div>
                                        <div class="flex items-center gap-4 text-xs text-gray-600 dark:text-gray-400">
                                            <span>Purpose: {{ ucfirst(str_replace('_', ' ', $call['purpose'])) }}</span>
                                            @if($call['branch'])
                                                <span>Branch: {{ $call['branch'] }}</span>
                                            @endif
                                            @if($call['agent_id'])
                                                <span>Agent: {{ substr($call['agent_id'], -8) }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <div class="text-right">
                                            <div class="text-lg font-semibold text-green-600 dark:text-green-400 tabular-nums">
                                                {{ $call['duration'] }}
                                            </div>
                                            <div class="text-xs text-gray-500">duration</div>
                                        </div>
                                        <div class="flex items-center">
                                            <span class="relative flex h-3 w-3">
                                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                                                <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                
                                {{-- Progress bar --}}
                                @if($call['duration_seconds'] > 30)
                                    <div class="mt-2">
                                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1">
                                            <div class="bg-green-600 h-1 rounded-full transition-all duration-1000"
                                                 style="width: {{ min(100, ($call['duration_seconds'] / 300) * 100) }}%">
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                    <x-heroicon-o-phone-x-mark class="w-12 h-12 mx-auto mb-3 text-gray-400" />
                    <p class="text-sm">No active calls at the moment</p>
                </div>
            @endif

            {{-- Recent Calls --}}
            @if($recentCalls->count() > 0)
                <div>
                    <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Recent Calls</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-left border-b border-gray-200 dark:border-gray-700">
                                    <th class="pb-2 font-medium text-gray-700 dark:text-gray-300">Customer</th>
                                    <th class="pb-2 font-medium text-gray-700 dark:text-gray-300">Status</th>
                                    <th class="pb-2 font-medium text-gray-700 dark:text-gray-300">Duration</th>
                                    <th class="pb-2 font-medium text-gray-700 dark:text-gray-300">Outcome</th>
                                    <th class="pb-2 font-medium text-gray-700 dark:text-gray-300">Ended</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($recentCalls as $call)
                                    <tr>
                                        <td class="py-2">
                                            <div>
                                                <div class="font-medium text-gray-900 dark:text-white">
                                                    {{ $call['customer_name'] }}
                                                </div>
                                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                                    {{ $call['to_number'] }}
                                                </div>
                                            </div>
                                        </td>
                                        <td class="py-2">
                                            <x-filament::badge 
                                                :color="match($call['status']) {
                                                    'completed' => 'success',
                                                    'failed' => 'danger',
                                                    'no-answer' => 'warning',
                                                    default => 'gray',
                                                }"
                                                size="sm"
                                            >
                                                {{ ucfirst($call['status']) }}
                                            </x-filament::badge>
                                        </td>
                                        <td class="py-2 text-gray-600 dark:text-gray-400">
                                            {{ $call['duration'] }}
                                        </td>
                                        <td class="py-2 text-gray-600 dark:text-gray-400">
                                            {{ $call['outcome'] }}
                                        </td>
                                        <td class="py-2 text-gray-500 dark:text-gray-400 text-xs">
                                            {{ $call['ended_at'] }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>