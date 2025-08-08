@php
    use App\Models\Company;
    use App\Models\Call;
    use App\Models\Appointment;
    use App\Models\Service;
    use Carbon\Carbon;
@endphp

<x-filament-panels::page>
    @php
        // Get selected company from Livewire component
        $selectedCompanyId = $this->companyId ?? null;
        $selectedPeriod = $this->period ?? 'week';
        
        // Get companies for dropdown
        $companies = Company::all();
        
        // Calculate date range based on period
        $endDate = now();
        $startDate = match($selectedPeriod) {
            'today' => now()->startOfDay(),
            'week' => now()->subWeek(),
            'month' => now()->subMonth(),
            'year' => now()->subYear(),
            default => now()->subWeek()
        };
        
        // Build query with optional company filter
        $appointmentsQuery = Appointment::whereBetween('starts_at', [$startDate, $endDate]);
        $callsQuery = Call::whereBetween('created_at', [$startDate, $endDate]);
        
        if ($selectedCompanyId) {
            $appointmentsQuery->where('company_id', $selectedCompanyId);
            $callsQuery->where('company_id', $selectedCompanyId);
        }
        
        // Calculate metrics
        $totalAppointments = $appointmentsQuery->count();
        $completedAppointments = (clone $appointmentsQuery)->where('status', 'completed')->count();
        $totalCalls = $callsQuery->count();
        $answeredCalls = (clone $callsQuery)->where('call_status', 'ended')->count();
        
        // Calculate revenue
        $totalRevenue = Appointment::query()
            ->join('services', 'appointments.service_id', '=', 'services.id')
            ->whereBetween('appointments.starts_at', [$startDate, $endDate])
            ->where('appointments.status', 'completed')
            ->when($selectedCompanyId, fn($q) => $q->where('appointments.company_id', $selectedCompanyId))
            ->sum('services.price');
        
        $conversionRate = $totalCalls > 0 ? round(($totalAppointments / $totalCalls) * 100, 1) : 0;
        
        // Get calls/appointments for today specifically
        $callsToday = Call::whereDate('created_at', today())
            ->when($selectedCompanyId, fn($q) => $q->where('company_id', $selectedCompanyId))
            ->count();
        
        $appointmentsToday = Appointment::whereDate('starts_at', today())
            ->when($selectedCompanyId, fn($q) => $q->where('company_id', $selectedCompanyId))
            ->count();
    @endphp
    
    <div class="space-y-6">
        {{-- Header with Filters --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Analytics Dashboard</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        {{ $selectedCompanyId ? Company::find($selectedCompanyId)->name : 'Alle Firmen' }} - 
                        {{ now()->format('d.m.Y H:i') }} Uhr
                    </p>
                </div>
            </div>
            
            {{-- Filter Section --}}
            <div class="flex gap-4 flex-wrap">
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Firma</label>
                    <select wire:model.live="companyId" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        <option value="">Alle Firmen</option>
                        @foreach($companies as $company)
                            <option value="{{ $company->id }}">{{ $company->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex-1 min-w-[150px]">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Zeitraum</label>
                    <select wire:model.live="period" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        <option value="today">Heute</option>
                        <option value="week">Diese Woche</option>
                        <option value="month">Dieser Monat</option>
                        <option value="year">Dieses Jahr</option>
                    </select>
                </div>
            </div>
        </div>
        
        {{-- KPI Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            {{-- Umsatz --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 p-3 bg-green-100 dark:bg-green-900 rounded-lg">
                        <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <text x="12" y="16" text-anchor="middle" stroke="currentColor" stroke-width="2" fill="none" font-size="14" font-weight="bold">€</text>
                            <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2" fill="none"/>
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                Gesamt-Umsatz
                            </dt>
                            <dd class="text-2xl font-semibold text-gray-900 dark:text-white">
                                {{ number_format($totalRevenue, 2, ',', '.') }} €
                            </dd>
                            <dd class="text-sm text-gray-500 dark:text-gray-400">
                                {{ ucfirst($selectedPeriod == 'today' ? 'Heute' : ($selectedPeriod == 'week' ? 'Diese Woche' : ($selectedPeriod == 'month' ? 'Dieser Monat' : 'Dieses Jahr'))) }}
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
            
            {{-- Anrufe --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 p-3 bg-blue-100 dark:bg-blue-900 rounded-lg">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                Anrufe
                            </dt>
                            <dd class="text-2xl font-semibold text-gray-900 dark:text-white">
                                {{ $totalCalls }}
                            </dd>
                            <dd class="text-sm text-green-600 dark:text-green-400">
                                {{ $callsToday }} heute • {{ $answeredCalls }} beantwortet
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
            
            {{-- Termine --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 p-3 bg-yellow-100 dark:bg-yellow-900 rounded-lg">
                        <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                Termine
                            </dt>
                            <dd class="text-2xl font-semibold text-gray-900 dark:text-white">
                                {{ $totalAppointments }}
                            </dd>
                            <dd class="text-sm text-green-600 dark:text-green-400">
                                {{ $appointmentsToday }} heute • {{ $completedAppointments }} abgeschlossen
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
            
            {{-- Conversion Rate --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 p-3 bg-purple-100 dark:bg-purple-900 rounded-lg">
                        <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                Conversion Rate
                            </dt>
                            <dd class="text-2xl font-semibold text-gray-900 dark:text-white">
                                {{ number_format($conversionRate, 1, ',', '.') }}%
                            </dd>
                            <dd class="text-sm text-gray-500 dark:text-gray-400">
                                Anrufe zu Terminen
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
        
        {{-- Charts Section --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- 7-Tage Trend --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">7-Tage Trend</h3>
                <div class="space-y-4">
                    @php
                        $dailyStats = [];
                        for ($i = 6; $i >= 0; $i--) {
                            $date = now()->subDays($i);
                            $dayAppointments = Appointment::whereDate('starts_at', $date)
                                ->when($selectedCompanyId, fn($q) => $q->where('company_id', $selectedCompanyId))
                                ->count();
                            $dayCalls = Call::whereDate('created_at', $date)
                                ->when($selectedCompanyId, fn($q) => $q->where('company_id', $selectedCompanyId))
                                ->count();
                            $dailyStats[] = [
                                'day' => $date->format('D'),
                                'date' => $date->format('d.m'),
                                'appointments' => $dayAppointments,
                                'calls' => $dayCalls
                            ];
                        }
                        $maxValue = max(array_merge(array_column($dailyStats, 'appointments'), array_column($dailyStats, 'calls'))) ?: 1;
                    @endphp
                    
                    <div class="flex items-end justify-between h-48 px-2">
                        @foreach($dailyStats as $stat)
                            <div class="flex flex-col items-center flex-1">
                                <div class="flex gap-1 items-end h-full">
                                    <div class="w-4 bg-blue-500 rounded-t" style="height: {{ ($stat['calls'] / $maxValue) * 100 }}%;" title="Anrufe: {{ $stat['calls'] }}"></div>
                                    <div class="w-4 bg-green-500 rounded-t" style="height: {{ ($stat['appointments'] / $maxValue) * 100 }}%;" title="Termine: {{ $stat['appointments'] }}"></div>
                                </div>
                                <div class="text-xs text-gray-600 dark:text-gray-400 mt-2">{{ $stat['day'] }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-500">{{ $stat['date'] }}</div>
                            </div>
                        @endforeach
                    </div>
                    
                    <div class="flex items-center justify-center gap-6 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 bg-blue-500 rounded"></div>
                            <span class="text-sm text-gray-600 dark:text-gray-400">Anrufe</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 bg-green-500 rounded"></div>
                            <span class="text-sm text-gray-600 dark:text-gray-400">Termine</span>
                        </div>
                    </div>
                </div>
            </div>
            
            {{-- Performance Metriken --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Performance Metriken</h3>
                <div class="space-y-4">
                    @php
                        $answerRate = $totalCalls > 0 ? ($answeredCalls / $totalCalls) * 100 : 0;
                        $completionRate = $totalAppointments > 0 ? ($completedAppointments / $totalAppointments) * 100 : 0;
                        $avgCallsPerDay = $totalCalls / max(1, $startDate->diffInDays($endDate));
                        $avgAppointmentsPerDay = $totalAppointments / max(1, $startDate->diffInDays($endDate));
                    @endphp
                    
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-gray-600 dark:text-gray-400">Antwortrate</span>
                            <span class="font-medium text-gray-900 dark:text-white">{{ number_format($answerRate, 1, ',', '.') }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700">
                            <div class="bg-blue-600 h-2.5 rounded-full" style="width: {{ min(100, $answerRate) }}%"></div>
                        </div>
                    </div>
                    
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-gray-600 dark:text-gray-400">Abschlussrate</span>
                            <span class="font-medium text-gray-900 dark:text-white">{{ number_format($completionRate, 1, ',', '.') }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700">
                            <div class="bg-green-600 h-2.5 rounded-full" style="width: {{ min(100, $completionRate) }}%"></div>
                        </div>
                    </div>
                    
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-gray-600 dark:text-gray-400">Conversion</span>
                            <span class="font-medium text-gray-900 dark:text-white">{{ number_format($conversionRate, 1, ',', '.') }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700">
                            <div class="bg-purple-600 h-2.5 rounded-full" style="width: {{ min(100, $conversionRate) }}%"></div>
                        </div>
                    </div>
                    
                    <div class="pt-4 border-t border-gray-200 dark:border-gray-700 grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Ø Anrufe/Tag</p>
                            <p class="text-xl font-semibold text-gray-900 dark:text-white">{{ number_format($avgCallsPerDay, 1, ',', '.') }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Ø Termine/Tag</p>
                            <p class="text-xl font-semibold text-gray-900 dark:text-white">{{ number_format($avgAppointmentsPerDay, 1, ',', '.') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        {{-- Recent Activities Table --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Letzte Aktivitäten</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Zeit</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Firma</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Kunde</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Typ</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @php
                            $recentActivities = [];
                            
                            // Get recent appointments
                            $recentAppointments = Appointment::with(['company', 'customer'])
                                ->when($selectedCompanyId, fn($q) => $q->where('company_id', $selectedCompanyId))
                                ->orderBy('created_at', 'desc')
                                ->limit(3)
                                ->get();
                            
                            foreach ($recentAppointments as $apt) {
                                $recentActivities[] = [
                                    'time' => $apt->created_at,
                                    'company' => $apt->company->name ?? 'Unbekannt',
                                    'customer' => $apt->customer->name ?? 'Gast',
                                    'type' => 'appointment',
                                    'status' => $apt->status
                                ];
                            }
                            
                            // Get recent calls
                            $recentCalls = Call::with(['company'])
                                ->when($selectedCompanyId, fn($q) => $q->where('company_id', $selectedCompanyId))
                                ->orderBy('created_at', 'desc')
                                ->limit(2)
                                ->get();
                            
                            foreach ($recentCalls as $call) {
                                $recentActivities[] = [
                                    'time' => $call->created_at,
                                    'company' => $call->company->name ?? 'Unbekannt',
                                    'customer' => $call->from_number ?? 'Anonym',
                                    'type' => 'call',
                                    'status' => $call->call_status
                                ];
                            }
                            
                            // Sort by time
                            usort($recentActivities, fn($a, $b) => $b['time']->timestamp - $a['time']->timestamp);
                        @endphp
                        
                        @forelse(array_slice($recentActivities, 0, 5) as $activity)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                {{ $activity['time']->format('H:i') }}
                                <span class="text-gray-500 dark:text-gray-400 block text-xs">{{ $activity['time']->format('d.m.Y') }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ $activity['company'] }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ $activity['customer'] }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($activity['type'] === 'appointment')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                                        Termin
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                        Anruf
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php
                                    $statusMap = [
                                        'completed' => ['label' => 'Abgeschlossen', 'color' => 'green'],
                                        'scheduled' => ['label' => 'Geplant', 'color' => 'blue'],
                                        'ended' => ['label' => 'Beendet', 'color' => 'green'],
                                        'no-answer' => ['label' => 'Verpasst', 'color' => 'red'],
                                        'busy' => ['label' => 'Besetzt', 'color' => 'yellow'],
                                    ];
                                    $statusInfo = $statusMap[$activity['status']] ?? ['label' => ucfirst($activity['status']), 'color' => 'gray'];
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $statusInfo['color'] }}-100 text-{{ $statusInfo['color'] }}-800 dark:bg-{{ $statusInfo['color'] }}-900 dark:text-{{ $statusInfo['color'] }}-200">
                                    {{ $statusInfo['label'] }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                                Keine Aktivitäten gefunden
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-filament-panels::page>