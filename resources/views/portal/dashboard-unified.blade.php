@extends('portal.layouts.unified')

@section('page-title', 'Dashboard')

@section('content')
<div class="p-6">
    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Anrufe heute</p>
                    <p class="text-2xl font-bold text-gray-800" id="calls-today">{{ $stats['total_calls_today'] ?? 0 }}</p>
                    <p class="text-sm text-gray-500 mt-1">
                        @if(isset($stats['calls_trend']) && $stats['calls_trend'] > 0)
                            <span class="text-green-500">↑ {{ $stats['calls_trend'] }}%</span>
                        @elseif(isset($stats['calls_trend']) && $stats['calls_trend'] < 0)
                            <span class="text-red-500">↓ {{ abs($stats['calls_trend']) }}%</span>
                        @else
                            <span class="text-gray-500">→ 0%</span>
                        @endif
                    </p>
                </div>
                <div class="p-3 bg-blue-100 rounded-lg">
                    <i class="fas fa-phone text-blue-600"></i>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Offene Anrufe</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $stats['open_calls'] ?? $stats['my_open_calls'] ?? 0 }}</p>
                    <p class="text-sm text-gray-500 mt-1">Aktion erforderlich</p>
                </div>
                <div class="p-3 bg-yellow-100 rounded-lg">
                    <i class="fas fa-exclamation-triangle text-yellow-600"></i>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Termine</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $stats['upcoming_appointments'] ?? 0 }}</p>
                    <p class="text-sm text-gray-500 mt-1">Anstehend</p>
                </div>
                <div class="p-3 bg-green-100 rounded-lg">
                    <i class="fas fa-calendar-check text-green-600"></i>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Offene Rechnungen</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $stats['open_invoices'] ?? 0 }}</p>
                    <p class="text-sm text-gray-500 mt-1">€{{ number_format($stats['total_due'] ?? 0, 2, ',', '.') }}</p>
                </div>
                <div class="p-3 bg-purple-100 rounded-lg">
                    <i class="fas fa-euro-sign text-purple-600"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Calls & Upcoming Tasks -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Recent Calls -->
        <div class="bg-white rounded-lg shadow">
            <div class="p-6 border-b">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold">Letzte Anrufe</h3>
                    <a href="{{ route('business.calls.index') }}" class="text-sm text-blue-600 hover:text-blue-800">
                        Alle anzeigen →
                    </a>
                </div>
            </div>
            <div class="divide-y divide-gray-200">
                @forelse($recentCalls as $call)
                <div class="p-4 hover:bg-gray-50">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <div class="flex items-center">
                                <span class="font-medium text-gray-900">{{ $call->phone_number }}</span>
                                @if($call->customer)
                                <span class="ml-2 text-sm text-gray-600">- {{ $call->customer->name }}</span>
                                @endif
                            </div>
                            <div class="mt-1 text-sm text-gray-500">
                                {{ $call->created_at->format('d.m.Y H:i') }} • 
                                {{ gmdate('i:s', $call->duration_sec ?? 0) }}
                            </div>
                        </div>
                        <div>
                            @php
                                $statusColors = [
                                    'new' => 'bg-blue-100 text-blue-800',
                                    'in_progress' => 'bg-yellow-100 text-yellow-800',
                                    'completed' => 'bg-green-100 text-green-800',
                                    'requires_action' => 'bg-red-100 text-red-800'
                                ];
                                $statusLabels = [
                                    'new' => 'Neu',
                                    'in_progress' => 'In Bearbeitung',
                                    'completed' => 'Abgeschlossen',
                                    'requires_action' => 'Aktion erforderlich'
                                ];
                            @endphp
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $statusColors[$call->portal_status] ?? 'bg-gray-100 text-gray-800' }}">
                                {{ $statusLabels[$call->portal_status] ?? $call->portal_status }}
                            </span>
                        </div>
                    </div>
                    @if($call->branch)
                    <div class="mt-1 text-xs text-gray-500">
                        <i class="fas fa-building mr-1"></i> {{ $call->branch->name }}
                    </div>
                    @endif
                </div>
                @empty
                <div class="p-8 text-center text-gray-500">
                    <i class="fas fa-phone-slash text-4xl mb-2"></i>
                    <p>Keine aktuellen Anrufe</p>
                </div>
                @endforelse
            </div>
        </div>

        <!-- Upcoming Tasks -->
        <div class="bg-white rounded-lg shadow">
            <div class="p-6 border-b">
                <h3 class="text-lg font-semibold">Anstehende Aufgaben</h3>
            </div>
            <div class="divide-y divide-gray-200">
                @forelse($upcomingTasks as $task)
                <a href="{{ $task['link'] }}" class="block p-4 hover:bg-gray-50">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <p class="font-medium text-gray-900">{{ $task['title'] }}</p>
                            <p class="mt-1 text-sm text-gray-500">
                                Fällig: {{ \Carbon\Carbon::parse($task['due_date'])->format('d.m.Y H:i') }}
                            </p>
                        </div>
                        <div>
                            @php
                                $priorityColors = [
                                    'high' => 'text-red-600',
                                    'medium' => 'text-yellow-600',
                                    'low' => 'text-green-600'
                                ];
                            @endphp
                            <i class="fas fa-flag {{ $priorityColors[$task['priority']] ?? 'text-gray-600' }}"></i>
                        </div>
                    </div>
                </a>
                @empty
                <div class="p-8 text-center text-gray-500">
                    <i class="fas fa-check-circle text-4xl mb-2"></i>
                    <p>Keine anstehenden Aufgaben</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Team Performance (if available) -->
    @if(isset($teamPerformance) && $teamPerformance->isNotEmpty())
    <div class="mt-6 bg-white rounded-lg shadow">
        <div class="p-6 border-b">
            <h3 class="text-lg font-semibold">Team Performance (7 Tage)</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mitarbeiter</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Anrufe</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Abgeschlossen</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Erfolgsquote</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ø Bearbeitungszeit</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($teamPerformance as $member)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10">
                                    <div class="h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center">
                                        <span class="text-sm font-medium text-gray-600">
                                            {{ substr($member['user']->name, 0, 1) }}
                                        </span>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900">{{ $member['user']->name }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $member['total_calls'] }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $member['completed_calls'] }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <span class="text-sm text-gray-900">{{ $member['completion_rate'] }}%</span>
                                <div class="ml-2 w-16 bg-gray-200 rounded-full h-2">
                                    <div class="bg-green-500 h-2 rounded-full" style="width: {{ $member['completion_rate'] }}%"></div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $member['avg_resolution_hours'] }}h
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
<script>
    // Auto-refresh dashboard data every 60 seconds
    setInterval(() => {
        if (window.apiClient) {
            window.apiClient.getDashboard().then(data => {
                // Update stats if needed
                console.log('Dashboard refreshed');
            }).catch(error => {
                console.error('Failed to refresh dashboard:', error);
            });
        }
    }, 60000);
</script>
@endpush
@endsection