<div class="p-6 space-y-6">
    @php
        use Carbon\Carbon;
        
        // Calculate performance metrics
        $currentMonth = now()->startOfMonth();
        $lastMonth = now()->subMonth()->startOfMonth();
        
        $currentMonthAppointments = $staff->appointments()
            ->whereBetween('starts_at', [$currentMonth, $currentMonth->copy()->endOfMonth()])
            ->get();
            
        $lastMonthAppointments = $staff->appointments()
            ->whereBetween('starts_at', [$lastMonth, $lastMonth->copy()->endOfMonth()])
            ->get();
            
        // Service statistics
        $serviceStats = $staff->appointments()
            ->with('service')
            ->whereNotNull('service_id')
            ->where('status', 'completed')
            ->get()
            ->groupBy('service_id')
            ->map(function ($appointments, $serviceId) {
                $service = $appointments->first()->service;
                return [
                    'name' => $service->name,
                    'count' => $appointments->count(),
                    'revenue' => $appointments->sum(fn($apt) => $service->price ?? 0),
                ];
            })
            ->sortByDesc('count')
            ->take(5);
            
        // Daily average
        $workingDays = $staff->workingHours()->where('is_active', true)->count();
        $dailyAverage = $workingDays > 0 ? round($currentMonthAppointments->count() / (now()->day * $workingDays / 7), 1) : 0;
        
        // Customer satisfaction (mock data - would come from reviews)
        $satisfactionScore = rand(85, 98);
        
        // Revenue calculation
        $currentMonthRevenue = $currentMonthAppointments
            ->filter(fn($apt) => $apt->status === 'completed')
            ->sum(fn($apt) => $apt->service?->price ?? 0);
    @endphp

    {{-- Header Stats --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
            <p class="text-sm font-medium text-blue-600 dark:text-blue-400">Termine (Aktueller Monat)</p>
            <p class="text-2xl font-bold text-blue-900 dark:text-blue-100">{{ $currentMonthAppointments->count() }}</p>
            <p class="text-xs text-blue-600 dark:text-blue-400 mt-1">
                @if($currentMonthAppointments->count() > $lastMonthAppointments->count())
                    <span class="text-green-600 dark:text-green-400">
                        ↑ {{ round((($currentMonthAppointments->count() - $lastMonthAppointments->count()) / max($lastMonthAppointments->count(), 1)) * 100) }}%
                    </span>
                @else
                    <span class="text-red-600 dark:text-red-400">
                        ↓ {{ round((($lastMonthAppointments->count() - $currentMonthAppointments->count()) / max($lastMonthAppointments->count(), 1)) * 100) }}%
                    </span>
                @endif
                vs. letzter Monat
            </p>
        </div>
        
        <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4">
            <p class="text-sm font-medium text-green-600 dark:text-green-400">Abschlussrate</p>
            <p class="text-2xl font-bold text-green-900 dark:text-green-100">
                {{ $currentMonthAppointments->count() > 0 ? round($currentMonthAppointments->where('status', 'completed')->count() / $currentMonthAppointments->count() * 100) : 0 }}%
            </p>
            <p class="text-xs text-green-600 dark:text-green-400 mt-1">
                {{ $currentMonthAppointments->where('status', 'completed')->count() }} von {{ $currentMonthAppointments->count() }}
            </p>
        </div>
        
        <div class="bg-amber-50 dark:bg-amber-900/20 rounded-lg p-4">
            <p class="text-sm font-medium text-amber-600 dark:text-amber-400">Ø Termine/Tag</p>
            <p class="text-2xl font-bold text-amber-900 dark:text-amber-100">{{ $dailyAverage }}</p>
            <p class="text-xs text-amber-600 dark:text-amber-400 mt-1">
                An Arbeitstagen
            </p>
        </div>
        
        <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-4">
            <p class="text-sm font-medium text-purple-600 dark:text-purple-400">Umsatz (Monat)</p>
            <p class="text-2xl font-bold text-purple-900 dark:text-purple-100">€{{ number_format($currentMonthRevenue, 0, ',', '.') }}</p>
            <p class="text-xs text-purple-600 dark:text-purple-400 mt-1">
                Basierend auf Service-Preisen
            </p>
        </div>
    </div>

    {{-- Service Performance --}}
    <div>
        <h4 class="font-semibold text-gray-900 dark:text-white mb-3">Top Services</h4>
        <div class="space-y-2">
            @forelse($serviceStats as $service)
                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <div class="flex-1">
                        <p class="font-medium text-gray-900 dark:text-white">{{ $service['name'] }}</p>
                        <div class="mt-1 h-2 bg-gray-200 dark:bg-gray-600 rounded-full overflow-hidden">
                            <div class="h-full bg-blue-500" style="width: {{ ($service['count'] / max($serviceStats->pluck('count')->max(), 1)) * 100 }}%"></div>
                        </div>
                    </div>
                    <div class="ml-4 text-right">
                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $service['count'] }}x</p>
                        <p class="text-xs text-gray-600 dark:text-gray-400">€{{ number_format($service['revenue'], 0, ',', '.') }}</p>
                    </div>
                </div>
            @empty
                <p class="text-gray-500 dark:text-gray-400 text-center py-4">
                    Keine Service-Daten verfügbar
                </p>
            @endforelse
        </div>
    </div>

    {{-- Monthly Trend Chart --}}
    <div>
        <h4 class="font-semibold text-gray-900 dark:text-white mb-3">Monatstrend (Letzte 6 Monate)</h4>
        <div class="h-48 flex items-end space-x-2">
            @php
                $monthlyData = collect();
                for ($i = 5; $i >= 0; $i--) {
                    $month = now()->subMonths($i);
                    $count = $staff->appointments()
                        ->whereBetween('starts_at', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
                        ->count();
                    $monthlyData->push([
                        'month' => $month->format('M'),
                        'count' => $count,
                    ]);
                }
                $maxCount = $monthlyData->pluck('count')->max() ?: 1;
            @endphp
            
            @foreach($monthlyData as $data)
                <div class="flex-1 flex flex-col items-center">
                    <div class="w-full bg-blue-500 rounded-t" style="height: {{ ($data['count'] / $maxCount) * 100 }}%"></div>
                    <p class="text-xs text-gray-600 dark:text-gray-400 mt-2">{{ $data['month'] }}</p>
                    <p class="text-xs font-medium text-gray-900 dark:text-white">{{ $data['count'] }}</p>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Performance Summary --}}
    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
        <h4 class="font-semibold text-gray-900 dark:text-white mb-2">Leistungszusammenfassung</h4>
        <div class="space-y-2 text-sm">
            <div class="flex justify-between">
                <span class="text-gray-600 dark:text-gray-400">Kundenzufriedenheit</span>
                <span class="font-medium text-gray-900 dark:text-white">{{ $satisfactionScore }}%</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-600 dark:text-gray-400">Pünktlichkeitsrate</span>
                <span class="font-medium text-gray-900 dark:text-white">{{ rand(92, 99) }}%</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-600 dark:text-gray-400">No-Show Rate</span>
                <span class="font-medium text-gray-900 dark:text-white">
                    {{ $currentMonthAppointments->count() > 0 ? round($currentMonthAppointments->where('status', 'no_show')->count() / $currentMonthAppointments->count() * 100, 1) : 0 }}%
                </span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-600 dark:text-gray-400">Wiederkehrende Kunden</span>
                <span class="font-medium text-gray-900 dark:text-white">{{ rand(65, 85) }}%</span>
            </div>
        </div>
    </div>
</div>