@php
    use App\Models\Company;
    use App\Models\Call;
    use App\Models\Appointment;
    use Carbon\Carbon;
@endphp

<x-filament-panels::page>
    @php
        
        // Hole alle Firmen mit deren Statistiken
        $companies = Company::withCount([
            'appointments as appointments_total',
            'appointments as appointments_completed' => function($query) {
                $query->where('status', 'completed');
            },
            'calls as calls_total',
            'calls as calls_answered' => function($query) {
                $query->where('call_status', 'ended');
            }
        ])->get();
        
        // Berechne Gesamt-Statistiken
        $totalCompanies = $companies->count();
        $totalAppointments = $companies->sum('appointments_total');
        $totalCalls = $companies->sum('calls_total');
        $activeCompanies = $companies->where('subscription_status', 'active')->count();
        
        // Top Performer
        $topByAppointments = $companies->sortByDesc('appointments_total')->take(5);
        $topByCalls = $companies->sortByDesc('calls_total')->take(5);
    @endphp
    
    <div class="space-y-6">
        {{-- Header --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Firmen-Analyse Dashboard</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Übersicht aller Firmen und deren Performance</p>
                </div>
                <div class="text-sm text-gray-500">
                    Stand: {{ now()->format('d.m.Y H:i') }} Uhr
                </div>
            </div>
        </div>
        
        {{-- KPI Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            {{-- Gesamt Firmen --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 p-3 bg-blue-100 dark:bg-blue-900 rounded-lg">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                Gesamt Firmen
                            </dt>
                            <dd class="text-2xl font-semibold text-gray-900 dark:text-white">
                                {{ $totalCompanies }}
                            </dd>
                            <dd class="text-sm text-green-600 dark:text-green-400">
                                {{ $activeCompanies }} aktiv
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
            
            {{-- Gesamt Termine --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 p-3 bg-green-100 dark:bg-green-900 rounded-lg">
                        <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                Gesamt Termine
                            </dt>
                            <dd class="text-2xl font-semibold text-gray-900 dark:text-white">
                                {{ number_format($totalAppointments, 0, ',', '.') }}
                            </dd>
                            <dd class="text-sm text-gray-500 dark:text-gray-400">
                                Ø {{ $totalCompanies > 0 ? round($totalAppointments / $totalCompanies) : 0 }} pro Firma
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
            
            {{-- Gesamt Anrufe --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 p-3 bg-purple-100 dark:bg-purple-900 rounded-lg">
                        <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                Gesamt Anrufe
                            </dt>
                            <dd class="text-2xl font-semibold text-gray-900 dark:text-white">
                                {{ number_format($totalCalls, 0, ',', '.') }}
                            </dd>
                            <dd class="text-sm text-gray-500 dark:text-gray-400">
                                Ø {{ $totalCompanies > 0 ? round($totalCalls / $totalCompanies) : 0 }} pro Firma
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
            
            {{-- Durchschnittliche Conversion --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 p-3 bg-yellow-100 dark:bg-yellow-900 rounded-lg">
                        <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                Ø Conversion Rate
                            </dt>
                            <dd class="text-2xl font-semibold text-gray-900 dark:text-white">
                                {{ $totalCalls > 0 ? number_format(($totalAppointments / $totalCalls) * 100, 1, ',', '.') : 0 }}%
                            </dd>
                            <dd class="text-sm text-gray-500 dark:text-gray-400">
                                Anrufe zu Terminen
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
        
        {{-- Top Performer Tables --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Top Firmen nach Terminen --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Top Firmen nach Terminen</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Firma
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Termine
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Abgeschlossen
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Rate
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($topByAppointments as $company)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $company->name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ $company->appointments_total }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ $company->appointments_completed }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    @php
                                        $rate = $company->appointments_total > 0 
                                            ? ($company->appointments_completed / $company->appointments_total) * 100 
                                            : 0;
                                    @endphp
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $rate > 70 ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : ($rate > 50 ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200') }}">
                                        {{ number_format($rate, 1, ',', '.') }}%
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            
            {{-- Top Firmen nach Anrufen --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Top Firmen nach Anrufen</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Firma
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Anrufe
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Beantwortet
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Rate
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($topByCalls as $company)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $company->name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ $company->calls_total }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ $company->calls_answered }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    @php
                                        $answerRate = $company->calls_total > 0 
                                            ? ($company->calls_answered / $company->calls_total) * 100 
                                            : 0;
                                    @endphp
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $answerRate > 80 ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : ($answerRate > 60 ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200') }}">
                                        {{ number_format($answerRate, 1, ',', '.') }}%
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        {{-- Alle Firmen Übersicht --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Alle Firmen im Überblick</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Firma
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Status
                            </th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Termine
                            </th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Anrufe
                            </th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Conversion
                            </th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Performance
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($companies as $company)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $company->name }}
                                </div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                    ID: {{ $company->id }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($company->subscription_status === 'active')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                        Aktiv
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                        {{ ucfirst($company->subscription_status ?? 'Inaktiv') }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-900 dark:text-white">
                                {{ $company->appointments_total }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-900 dark:text-white">
                                {{ $company->calls_total }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm">
                                @php
                                    $conversion = $company->calls_total > 0 
                                        ? ($company->appointments_total / $company->calls_total) * 100 
                                        : 0;
                                @endphp
                                <span class="font-medium {{ $conversion > 50 ? 'text-green-600 dark:text-green-400' : ($conversion > 25 ? 'text-yellow-600 dark:text-yellow-400' : 'text-red-600 dark:text-red-400') }}">
                                    {{ number_format($conversion, 1, ',', '.') }}%
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <div class="flex items-center justify-center">
                                    @php
                                        $performance = ($company->appointments_completed + $company->calls_answered) / 2;
                                        $maxPerformance = max(1, ($company->appointments_total + $company->calls_total) / 2);
                                        $performancePercent = $maxPerformance > 0 ? ($performance / $maxPerformance) * 100 : 0;
                                    @endphp
                                    <div class="w-24 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                        <div class="h-2 rounded-full {{ $performancePercent > 70 ? 'bg-green-600' : ($performancePercent > 40 ? 'bg-yellow-600' : 'bg-red-600') }}" 
                                             style="width: {{ min(100, $performancePercent) }}%"></div>
                                    </div>
                                    <span class="ml-2 text-xs text-gray-500 dark:text-gray-400">
                                        {{ number_format($performancePercent, 0) }}%
                                    </span>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-filament-panels::page>