<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Metrics Overview --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            @php
                $metrics = $this->getMetrics();
                $data = $metrics['metrics'];
                $derived = $metrics['derived'];
            @endphp

            {{-- Reschedule Offered --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Reschedule Angeboten</p>
                        <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $data['reschedule_offered'] }}</p>
                    </div>
                    <div class="p-3 bg-blue-100 dark:bg-blue-900 rounded-full">
                        <x-heroicon-o-arrow-path class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                    </div>
                </div>
            </div>

            {{-- Reschedule Accepted --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Reschedule Akzeptiert</p>
                        <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $data['reschedule_accepted'] }}</p>
                        <p class="text-xs text-green-600 dark:text-green-400 mt-1">{{ $derived['conversion_rate_percent'] }}% Conversion</p>
                    </div>
                    <div class="p-3 bg-green-100 dark:bg-green-900 rounded-full">
                        <x-heroicon-o-check-circle class="w-6 h-6 text-green-600 dark:text-green-400" />
                    </div>
                </div>
            </div>

            {{-- Reschedule Declined --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Reschedule Abgelehnt</p>
                        <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $data['reschedule_declined'] }}</p>
                        <p class="text-xs text-red-600 dark:text-red-400 mt-1">{{ $derived['decline_rate_percent'] }}% Decline Rate</p>
                    </div>
                    <div class="p-3 bg-red-100 dark:bg-red-900 rounded-full">
                        <x-heroicon-o-x-circle class="w-6 h-6 text-red-600 dark:text-red-400" />
                    </div>
                </div>
            </div>

            {{-- Branch Notified --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Filiale Benachrichtigt</p>
                        <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $data['branch_notified'] }}</p>
                    </div>
                    <div class="p-3 bg-yellow-100 dark:bg-yellow-900 rounded-full">
                        <x-heroicon-o-bell class="w-6 h-6 text-yellow-600 dark:text-yellow-400" />
                    </div>
                </div>
            </div>
        </div>

        {{-- Period Info --}}
        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
            <div class="flex items-center">
                <x-heroicon-o-information-circle class="w-5 h-5 text-blue-600 dark:text-blue-400 mr-2" />
                <span class="text-sm text-blue-800 dark:text-blue-300">
                    Zeitraum: {{ $metrics['period']['start'] }} bis {{ $metrics['period']['end'] }}
                </span>
            </div>
        </div>

        {{-- Metrics by Branch Table --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Metriken nach Filiale</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Filiale</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Angeboten</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Akzeptiert</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Abgelehnt</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Benachrichtigt</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Conversion</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($this->getMetricsByBranch() as $row)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">{{ $row->branch_name }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $row->reschedule_offered }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600 dark:text-green-400">{{ $row->reschedule_accepted }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600 dark:text-red-400">{{ $row->reschedule_declined }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $row->branch_notified }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    @if($row->reschedule_offered > 0)
                                        {{ round(($row->reschedule_accepted / $row->reschedule_offered) * 100, 1) }}%
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ADR-005 Info --}}
        <div class="bg-gray-50 dark:bg-gray-900/50 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
            <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-2">ADR-005: Non-blocking Cancellation Policy</h4>
            <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                <li>• Storno/Reschedule: Jederzeit erlaubt (Cutoff = 0)</li>
                <li>• Reschedule-First: Agent bietet Umbuchung vor Stornierung an</li>
                <li>• Branch-Notifications: Dual Channel (Email + Filament UI)</li>
                <li>• Telemetrie: 4 Counter mit Correlation (call_id, booking_id, branch_id, service_id)</li>
            </ul>
        </div>
    </div>
</x-filament-panels::page>
