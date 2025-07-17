@php
    use App\Models\BalanceTransaction;
    use Carbon\Carbon;
    
    $record = $getRecord();
    $now = Carbon::now();
    
    // Monatsstatistiken
    $currentMonth = $record->transactions()
        ->where('created_at', '>=', $now->startOfMonth())
        ->where('type', 'debit')
        ->sum('amount');
        
    $lastMonth = $record->transactions()
        ->where('created_at', '>=', $now->copy()->subMonth()->startOfMonth())
        ->where('created_at', '<=', $now->copy()->subMonth()->endOfMonth())
        ->where('type', 'debit')
        ->sum('amount');
        
    $monthlyChange = $lastMonth > 0 ? (($currentMonth - $lastMonth) / $lastMonth) * 100 : 0;
    
    // Täglicher Durchschnitt
    $dailyAvg = $record->transactions()
        ->where('created_at', '>=', $now->subDays(30))
        ->where('type', 'debit')
        ->sum('amount') / 30;
        
    // Prognose für Restmonat
    $daysInMonth = $now->daysInMonth;
    $daysPassed = $now->day;
    $projectedMonthly = $daysPassed > 0 ? ($currentMonth / $daysPassed) * $daysInMonth : 0;
    
    // Top 5 Ausgabentage
    $topDays = $record->transactions()
        ->where('type', 'debit')
        ->where('created_at', '>=', $now->startOfMonth())
        ->selectRaw('DATE(created_at) as date, SUM(amount) as total')
        ->groupBy('date')
        ->orderByDesc('total')
        ->limit(5)
        ->get();
@endphp

<div class="grid grid-cols-1 md:grid-cols-4 gap-4">
    {{-- Aktueller Monat --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between mb-2">
            <span class="text-sm text-gray-600 dark:text-gray-400">Aktueller Monat</span>
            @if($monthlyChange != 0)
                <span class="text-xs px-2 py-1 rounded-full {{ $monthlyChange > 0 ? 'bg-red-100 text-red-600 dark:bg-red-900/20 dark:text-red-400' : 'bg-green-100 text-green-600 dark:bg-green-900/20 dark:text-green-400' }}">
                    {{ $monthlyChange > 0 ? '+' : '' }}{{ number_format($monthlyChange, 1) }}%
                </span>
            @endif
        </div>
        <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">
            {{ number_format($currentMonth, 2) }}€
        </div>
        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
            {{ $now->format('F Y') }}
        </div>
    </div>
    
    {{-- Täglicher Durchschnitt --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between mb-2">
            <span class="text-sm text-gray-600 dark:text-gray-400">Ø pro Tag</span>
            <x-heroicon-o-calculator class="h-4 w-4 text-gray-400" />
        </div>
        <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">
            {{ number_format($dailyAvg, 2) }}€
        </div>
        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
            Letzte 30 Tage
        </div>
    </div>
    
    {{-- Prognose --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between mb-2">
            <span class="text-sm text-gray-600 dark:text-gray-400">Prognose Monat</span>
            <x-heroicon-o-arrow-trending-up class="h-4 w-4 text-gray-400" />
        </div>
        <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">
            {{ number_format($projectedMonthly, 2) }}€
        </div>
        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
            Bei aktuellem Verbrauch
        </div>
    </div>
    
    {{-- Guthaben reicht für --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between mb-2">
            <span class="text-sm text-gray-600 dark:text-gray-400">Guthaben reicht</span>
            <x-heroicon-o-clock class="h-4 w-4 text-gray-400" />
        </div>
        <div class="text-2xl font-bold {{ $dailyAvg > 0 && $record->balance / $dailyAvg < 7 ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-gray-100' }}">
            @if($dailyAvg > 0)
                {{ number_format($record->balance / $dailyAvg, 0) }} Tage
            @else
                ∞
            @endif
        </div>
        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
            Bei aktuellem Verbrauch
        </div>
    </div>
</div>

{{-- Top Ausgabentage --}}
@if($topDays->count() > 0)
    <div class="mt-6">
        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Top Ausgabentage diesen Monat</h4>
        <div class="space-y-2">
            @foreach($topDays as $day)
                <div class="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-900/50 rounded">
                    <span class="text-sm text-gray-600 dark:text-gray-400">
                        {{ Carbon::parse($day->date)->format('d.m.Y (l)') }}
                    </span>
                    <span class="font-medium text-gray-900 dark:text-gray-100">
                        {{ number_format($day->total, 2) }}€
                    </span>
                </div>
            @endforeach
        </div>
    </div>
@endif