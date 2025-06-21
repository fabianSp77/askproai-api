<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <span>Live Appointment Board</span>
                <span class="text-xs text-gray-500">Auto-refresh: {{ $refreshInterval }}s</span>
            </div>
        </x-slot>

        <x-slot name="headerActions">
            <input type="date" wire:model.live="selectedDate" class="block w-full transition duration-75 rounded-lg shadow-sm outline-none focus:ring-1 focus:ring-inset dark:bg-gray-700 dark:text-white dark:focus:border-primary-500 border-gray-300 dark:border-gray-600 focus:border-primary-500 focus:ring-primary-500" />
            
            @if(count($this->getBranches()) > 1)
                <select wire:model.live="selectedBranchId" class="block w-full transition duration-75 rounded-lg shadow-sm outline-none focus:ring-1 focus:ring-inset dark:bg-gray-700 dark:text-white dark:focus:border-primary-500 border-gray-300 dark:border-gray-600 focus:border-primary-500 focus:ring-primary-500">
                    <option value="">All Branches</option>
                    @foreach($this->getBranches() as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
            @endif
            
            <x-filament::button wire:click="refresh" size="sm" color="gray">
                <x-heroicon-m-arrow-path class="w-4 h-4" />
            </x-filament::button>
        </x-slot>

        <!-- Current Status Overview -->
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-8 gap-2 mb-6">
            <div class="text-center p-2 bg-gray-50 dark:bg-gray-800 rounded">
                <div class="text-xl font-bold">{{ $liveData['current_status']['total'] ?? 0 }}</div>
                <div class="text-xs text-gray-500">Total</div>
            </div>
            <div class="text-center p-2 bg-blue-50 dark:bg-blue-900/20 rounded">
                <div class="text-xl font-bold text-blue-600">{{ $liveData['current_status']['scheduled'] ?? 0 }}</div>
                <div class="text-xs text-gray-500">Scheduled</div>
            </div>
            <div class="text-center p-2 bg-green-50 dark:bg-green-900/20 rounded">
                <div class="text-xl font-bold text-green-600">{{ $liveData['current_status']['confirmed'] ?? 0 }}</div>
                <div class="text-xs text-gray-500">Confirmed</div>
            </div>
            <div class="text-center p-2 bg-purple-50 dark:bg-purple-900/20 rounded">
                <div class="text-xl font-bold text-purple-600">{{ $liveData['current_status']['in_progress'] ?? 0 }}</div>
                <div class="text-xs text-gray-500">In Progress</div>
            </div>
            <div class="text-center p-2 bg-emerald-50 dark:bg-emerald-900/20 rounded">
                <div class="text-xl font-bold text-emerald-600">{{ $liveData['current_status']['completed'] ?? 0 }}</div>
                <div class="text-xs text-gray-500">Completed</div>
            </div>
            <div class="text-center p-2 bg-orange-50 dark:bg-orange-900/20 rounded">
                <div class="text-xl font-bold text-orange-600">{{ $liveData['current_status']['cancelled'] ?? 0 }}</div>
                <div class="text-xs text-gray-500">Cancelled</div>
            </div>
            <div class="text-center p-2 bg-red-50 dark:bg-red-900/20 rounded">
                <div class="text-xl font-bold text-red-600">{{ $liveData['current_status']['no_show'] ?? 0 }}</div>
                <div class="text-xs text-gray-500">No-Show</div>
            </div>
            <div class="text-center p-2 bg-yellow-50 dark:bg-yellow-900/20 rounded">
                <div class="text-xl font-bold text-yellow-600">{{ $liveData['current_status']['late_arrivals'] ?? 0 }}</div>
                <div class="text-xs text-gray-500">Late</div>
            </div>
        </div>

        <!-- Active Alerts -->
        @if(count($liveData['alerts'] ?? []) > 0)
            <div class="mb-6">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Active Alerts</h3>
                <div class="space-y-2">
                    @foreach($liveData['alerts'] as $alert)
                        <div class="flex items-start gap-3 p-3 rounded-lg {{ $alert['severity'] === 'error' ? 'bg-red-50 dark:bg-red-900/20' : ($alert['severity'] === 'warning' ? 'bg-yellow-50 dark:bg-yellow-900/20' : 'bg-blue-50 dark:bg-blue-900/20') }}">
                            @if($alert['severity'] === 'error')
                                <x-heroicon-o-exclamation-circle class="w-5 h-5 text-red-500 flex-shrink-0" />
                            @elseif($alert['severity'] === 'warning')
                                <x-heroicon-o-exclamation-triangle class="w-5 h-5 text-yellow-500 flex-shrink-0" />
                            @else
                                <x-heroicon-o-information-circle class="w-5 h-5 text-blue-500 flex-shrink-0" />
                            @endif
                            <div class="flex-1">
                                <p class="font-medium text-sm">{{ $alert['message'] }}</p>
                                <p class="text-xs text-gray-600 dark:text-gray-400">{{ $alert['details'] }}</p>
                            </div>
                            <span class="text-xs text-gray-500">{{ $alert['action'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Time Slot Overview -->
            <div class="lg:col-span-2">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Time Slot Overview</h3>
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="grid grid-cols-13 text-xs">
                        <div class="p-2 font-medium text-gray-600 dark:text-gray-400">Time</div>
                        @foreach($liveData['time_slots'] ?? [] as $slot)
                            <div class="p-2 text-center border-l border-gray-200 dark:border-gray-700 {{ $slot['is_current'] ? 'bg-blue-50 dark:bg-blue-900/20' : ($slot['is_past'] ? 'bg-gray-50 dark:bg-gray-900/50' : '') }}">
                                <div class="font-medium">{{ $slot['label'] }}</div>
                                <div class="text-gray-500">{{ $slot['appointments'] }}</div>
                                <div class="mt-1">
                                    <div class="h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                        <div class="h-full {{ $slot['utilization'] >= 90 ? 'bg-red-500' : ($slot['utilization'] >= 70 ? 'bg-yellow-500' : 'bg-green-500') }}" 
                                             style="width: {{ $slot['utilization'] }}%"></div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Upcoming Appointments -->
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mt-6 mb-3">Upcoming Appointments</h3>
                <div class="space-y-2">
                    @forelse($liveData['upcoming_appointments'] ?? [] as $appointment)
                        <div class="flex items-center justify-between p-3 bg-white dark:bg-gray-800 rounded-lg border {{ $appointment['is_urgent'] ? 'border-red-300' : ($appointment['is_soon'] ? 'border-yellow-300' : 'border-gray-200 dark:border-gray-700') }}">
                            <div class="flex items-center gap-4">
                                <div class="text-center">
                                    <div class="text-lg font-bold">{{ $appointment['time'] }}</div>
                                    <div class="text-xs text-gray-500">{{ $appointment['minutes_until'] }}m</div>
                                </div>
                                <div>
                                    <p class="font-medium">{{ $appointment['customer'] }}</p>
                                    <p class="text-sm text-gray-500">{{ $appointment['service'] }} • {{ $appointment['staff'] }}</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="text-xs px-2 py-1 rounded-full {{ $appointment['status'] === 'confirmed' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800' }}">
                                    {{ ucfirst($appointment['status']) }}
                                </span>
                                <p class="text-xs text-gray-500 mt-1">{{ $appointment['branch'] }}</p>
                            </div>
                        </div>
                    @empty
                        <p class="text-center text-gray-500 py-4">No upcoming appointments</p>
                    @endforelse
                </div>
            </div>

            <!-- Right Sidebar -->
            <div class="space-y-6">
                <!-- Queue Status -->
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Call Queue Status</h3>
                    <div class="space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Active Calls</span>
                            <span class="font-medium">{{ $liveData['queue_status']['active_calls'] ?? 0 }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Queued</span>
                            <span class="font-medium {{ ($liveData['queue_status']['queued_calls'] ?? 0) > 3 ? 'text-red-600' : '' }}">
                                {{ $liveData['queue_status']['queued_calls'] ?? 0 }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Avg Wait</span>
                            <span class="font-medium">{{ $liveData['queue_status']['avg_wait_time'] ?? 0 }}s</span>
                        </div>
                        <div class="pt-2 border-t border-gray-200 dark:border-gray-700">
                            <span class="text-xs px-2 py-1 rounded-full {{ ($liveData['queue_status']['status'] ?? 'idle') === 'busy' ? 'bg-red-100 text-red-800' : (($liveData['queue_status']['status'] ?? 'idle') === 'normal' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800') }}">
                                {{ ucfirst($liveData['queue_status']['status'] ?? 'idle') }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Recent Activities -->
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Recent Activities</h3>
                    <div class="space-y-3 max-h-64 overflow-y-auto">
                        @forelse($liveData['recent_activities'] ?? [] as $activity)
                            <div class="flex items-start gap-3">
                                <div class="flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center {{ $activity['color'] === 'green' ? 'bg-green-100' : ($activity['color'] === 'red' ? 'bg-red-100' : 'bg-blue-100') }}">
                                    <x-dynamic-component :component="$activity['icon']" class="w-4 h-4 {{ $activity['color'] === 'green' ? 'text-green-600' : ($activity['color'] === 'red' ? 'text-red-600' : 'text-blue-600') }}" />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $activity['message'] }}</p>
                                    <p class="text-xs text-gray-500">{{ $activity['details'] }} • {{ $activity['time'] }}</p>
                                </div>
                            </div>
                        @empty
                            <p class="text-center text-gray-500 py-4 text-sm">No recent activities</p>
                        @endforelse
                    </div>
                </div>

                <!-- Next Appointment Card -->
                @if($liveData['current_status']['next_appointment'] ?? null)
                    <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg p-4 text-white">
                        <h3 class="text-sm font-semibold mb-2">Next Appointment</h3>
                        <div class="text-2xl font-bold mb-1">{{ $liveData['current_status']['next_appointment']['time'] }}</div>
                        <p class="text-sm opacity-90">{{ $liveData['current_status']['next_appointment']['customer'] }}</p>
                        <p class="text-xs opacity-75">{{ $liveData['current_status']['next_appointment']['service'] }}</p>
                        <p class="text-xs opacity-75">with {{ $liveData['current_status']['next_appointment']['staff'] }}</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Staff Availability -->
        <div class="mt-6">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Staff Availability</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($liveData['staff_availability'] ?? [] as $branch)
                    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                        <h4 class="font-medium text-gray-900 dark:text-white mb-3">
                            {{ $branch['branch_name'] }}
                            <span class="text-sm text-gray-500">({{ $branch['available_staff'] }}/{{ $branch['total_staff'] }} available)</span>
                        </h4>
                        <div class="space-y-2">
                            @foreach($branch['staff'] as $staff)
                                <div class="flex items-center justify-between text-sm">
                                    <div class="flex items-center gap-2">
                                        <div class="w-2 h-2 rounded-full {{ $staff['status'] === 'busy' ? 'bg-red-500' : 'bg-green-500' }}"></div>
                                        <span class="{{ $staff['status'] === 'busy' ? 'text-gray-500' : '' }}">{{ $staff['name'] }}</span>
                                    </div>
                                    <div class="text-right">
                                        @if($staff['status'] === 'busy' && $staff['current_appointment'])
                                            <span class="text-xs text-gray-500">Busy until {{ $staff['current_appointment']['ends_at'] }}</span>
                                        @elseif($staff['next_appointment'])
                                            <span class="text-xs text-gray-500">Next: {{ $staff['next_appointment']['time'] }}</span>
                                        @else
                                            <span class="text-xs text-green-600">Available</span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </x-filament::section>

    @if($refreshInterval > 0)
        <div wire:poll.{{ $refreshInterval }}s="refresh"></div>
    @endif
</x-filament-widgets::widget>