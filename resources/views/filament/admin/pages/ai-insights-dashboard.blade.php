@php
    use App\Models\Company;
    use App\Models\Call;
    use App\Models\Appointment;
    use Carbon\Carbon;
    
    // Hole Daten vom Controller
    $aiMetrics = $this->aiMetrics ?? [];
    $costBenefit = $this->costBenefit ?? [];
    $heatmapData = $this->heatmapData ?? [];
    $funnelData = $this->funnelData ?? [];
    $industryData = $this->industryData ?? [];
    $learningCurve = $this->learningCurve ?? [];
    $realtimeMetrics = $this->realtimeMetrics ?? [];
@endphp

<x-filament-panels::page>
    @php
        $selectedCompanyId = $this->companyId ?? null;
        $selectedPeriod = $this->period ?? 'week';
        
        // Get companies for dropdown
        $companies = Company::all();
    @endphp
    
    <div class="space-y-6">
        {{-- Header mit Filter --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">🤖 AI-Insights Dashboard</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Echte Daten aus Ihrem AI-Telefon-System
                    </p>
                </div>
                <div class="text-sm text-gray-500">
                    Stand: {{ now()->format('d.m.Y H:i') }} Uhr
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

        {{-- 1. AI-Verständnis-Rate Chart --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                    🎯 AI-Verständnis-Rate
                </h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                    Basierend auf Call-Status Analyse
                </p>
                
                @if(!empty($aiMetrics))
                <div class="space-y-3">
                    @foreach(['perfekt' => 'Erfolgreich beendet', 'gut' => 'Keine Antwort', 'nachfragen' => 'Besetzt', 'übertragen' => 'Fehlgeschlagen'] as $key => $label)
                        @if(isset($aiMetrics[$key]))
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-gray-600 dark:text-gray-400">{{ $label }}</span>
                                <span class="font-medium text-gray-900 dark:text-white">{{ $aiMetrics[$key] }}%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-3 dark:bg-gray-700">
                                <div class="h-3 rounded-full transition-all duration-500 
                                    {{ $key === 'perfekt' ? 'bg-green-500' : 
                                       ($key === 'gut' ? 'bg-blue-500' : 
                                       ($key === 'nachfragen' ? 'bg-yellow-500' : 'bg-red-500')) }}" 
                                    style="width: {{ $aiMetrics[$key] }}%"></div>
                            </div>
                        </div>
                        @endif
                    @endforeach
                </div>
                
                @if(isset($aiMetrics['success_rate']))
                <div class="mt-4 p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
                    <p class="text-sm text-green-800 dark:text-green-200">
                        💡 <strong>{{ $aiMetrics['success_rate'] }}%</strong> erfolgreiche AI-Interaktionen
                    </p>
                </div>
                @endif
                @else
                <p class="text-gray-500 dark:text-gray-400">Keine Daten verfügbar</p>
                @endif
            </div>

            {{-- 2. Kosten-Nutzen-Analyse --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                    💰 Kosten-Nutzen-Analyse
                </h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                    ROI pro Anruf in €
                </p>
                
                @if(!empty($costBenefit))
                <div class="grid grid-cols-2 gap-4">
                    <div class="text-center p-4 bg-red-50 dark:bg-red-900/20 rounded-lg">
                        <p class="text-2xl font-bold text-red-600 dark:text-red-400">
                            {{ number_format($costBenefit['kosten_pro_anruf'] ?? 0, 2, ',', '.') }} €
                        </p>
                        <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">Kosten/Anruf</p>
                    </div>
                    <div class="text-center p-4 bg-green-50 dark:bg-green-900/20 rounded-lg">
                        <p class="text-2xl font-bold text-green-600 dark:text-green-400">
                            {{ number_format($costBenefit['roi'] ?? 0, 2, ',', '.') }} €
                        </p>
                        <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">Gewinn/Anruf</p>
                    </div>
                </div>
                
                <div class="mt-4">
                    <p class="text-sm text-gray-600 dark:text-gray-400">ROI-Multiplikator</p>
                    <p class="text-3xl font-bold text-green-600 dark:text-green-400">
                        {{ number_format($costBenefit['roi_multiplikator'] ?? 0, 1, ',', '.') }}x
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        Ø Terminwert: {{ number_format($costBenefit['durchschnittlicher_terminwert'] ?? 0, 2, ',', '.') }} €
                    </p>
                </div>
                @else
                <p class="text-gray-500 dark:text-gray-400">Keine Daten verfügbar</p>
                @endif
            </div>
        </div>

        {{-- 3. Spitzenzeiten-Heatmap --}}
        @if(!empty($heatmapData))
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                🔥 Anruf-Heatmap (Echte Daten)
            </h3>
            
            @php
                $days = ['Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So'];
                $hours = range(8, 20);
                
                // Finde Maximum für Skalierung
                $maxCalls = 0;
                foreach ($heatmapData as $dayData) {
                    foreach ($dayData as $count) {
                        $maxCalls = max($maxCalls, $count);
                    }
                }
            @endphp
            
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr>
                            <th class="px-2 py-1 text-xs font-medium text-gray-500 dark:text-gray-400">Zeit</th>
                            @foreach($hours as $hour)
                                <th class="px-2 py-1 text-xs font-medium text-gray-500 dark:text-gray-400">{{ $hour }}h</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($days as $day)
                        <tr>
                            <td class="px-2 py-1 text-xs font-medium text-gray-700 dark:text-gray-300">{{ $day }}</td>
                            @foreach($hours as $hour)
                                @php
                                    $count = $heatmapData[$day][$hour] ?? 0;
                                    $intensity = $maxCalls > 0 ? ($count / $maxCalls) * 100 : 0;
                                    $color = $intensity > 80 ? 'bg-red-500' : 
                                            ($intensity > 60 ? 'bg-orange-500' : 
                                            ($intensity > 40 ? 'bg-yellow-500' : 
                                            ($intensity > 20 ? 'bg-green-400' : 'bg-gray-200')));
                                    $opacity = max(0.3, $intensity / 100);
                                @endphp
                                <td class="px-1 py-1">
                                    <div class="w-8 h-8 rounded {{ $color }} flex items-center justify-center text-xs font-medium text-white"
                                         style="opacity: {{ $opacity }}"
                                         title="{{ $day }} {{ $hour }}:00 - {{ $count }} Anrufe">
                                        {{ $count > 0 ? $count : '' }}
                                    </div>
                                </td>
                            @endforeach
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            <div class="mt-4 flex items-center gap-4 text-xs text-gray-600 dark:text-gray-400">
                <span>Wenig</span>
                <div class="flex gap-1">
                    <div class="w-4 h-4 bg-gray-200 rounded"></div>
                    <div class="w-4 h-4 bg-green-400 rounded"></div>
                    <div class="w-4 h-4 bg-yellow-500 rounded"></div>
                    <div class="w-4 h-4 bg-orange-500 rounded"></div>
                    <div class="w-4 h-4 bg-red-500 rounded"></div>
                </div>
                <span>Viel</span>
                <span class="ml-auto">Maximum: {{ $maxCalls }} Anrufe/Stunde</span>
            </div>
        </div>
        @endif

        {{-- 4. Conversion Funnel --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            @if(!empty($funnelData))
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                    🎯 Conversion-Funnel (Echte Daten)
                </h3>
                
                <div class="space-y-2">
                    @foreach($funnelData as $index => $stage)
                        <div class="relative">
                            <div class="flex items-center justify-between p-3 rounded-lg"
                                 style="background: linear-gradient(to right, 
                                        {{ $stage['percentage'] > 80 ? 'rgb(34 197 94 / 20%)' : 
                                           ($stage['percentage'] > 50 ? 'rgb(59 130 246 / 20%)' : 
                                           ($stage['percentage'] > 25 ? 'rgb(251 146 60 / 20%)' : 'rgb(239 68 68 / 20%)')) }} {{ $stage['percentage'] }}%, 
                                        transparent {{ $stage['percentage'] }}%)">
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $stage['stage'] }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ number_format($stage['count'], 0, ',', '.') }} ({{ $stage['percentage'] }}%)
                                    </p>
                                </div>
                                @if($index < count($funnelData) - 1)
                                    @php
                                        $dropOff = $stage['percentage'] - $funnelData[$index + 1]['percentage'];
                                    @endphp
                                    @if($dropOff > 0)
                                    <span class="text-xs text-red-600 dark:text-red-400">
                                        -{{ number_format($dropOff, 1, ',', '.') }}%
                                    </span>
                                    @endif
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- 5. Branchen-Performance --}}
            @if(!empty($industryData))
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                    🏢 Branchen-Performance (Echte Daten)
                </h3>
                
                <div class="space-y-3">
                    @foreach($industryData as $industry)
                        <div class="border-l-4 border-blue-500 pl-3">
                            <div class="flex justify-between items-start">
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $industry['name'] }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $industry['calls'] }} Anrufe | {{ $industry['appointments'] }} Termine
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-bold text-green-600 dark:text-green-400">
                                        {{ number_format($industry['revenue'], 0, ',', '.') }} €
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ number_format($industry['conversion'], 1, ',', '.') }}% Conv.
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                    
                    @if(empty($industryData))
                    <p class="text-gray-500 dark:text-gray-400">Keine Branchendaten verfügbar</p>
                    @endif
                </div>
            </div>
            @endif
        </div>

        {{-- 6. AI-Lernkurve --}}
        @if(!empty($learningCurve))
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                📈 AI-Lernkurve (12 Wochen Verlauf)
            </h3>
            
            @php
                $maxAccuracy = 100;
                $firstWeek = $learningCurve[0] ?? null;
                $lastWeek = $learningCurve[count($learningCurve)-1] ?? null;
            @endphp
            
            <div class="h-64 flex items-end justify-between gap-2">
                @foreach($learningCurve as $week)
                    <div class="flex-1 flex flex-col items-center">
                        <div class="w-full bg-gradient-to-t from-blue-500 to-green-500 rounded-t-lg transition-all duration-500 hover:opacity-80"
                             style="height: {{ ($week['accuracy'] / $maxAccuracy) * 16 }}rem"
                             title="{{ $week['week'] }}: {{ number_format($week['accuracy'], 1, ',', '.') }}% Genauigkeit ({{ $week['calls'] }} Anrufe)">
                        </div>
                        <p class="text-xs text-gray-600 dark:text-gray-400 mt-2 rotate-45 origin-left">
                            {{ $week['week'] }}
                        </p>
                    </div>
                @endforeach
            </div>
            
            @if($firstWeek && $lastWeek)
            <div class="mt-8 grid grid-cols-3 gap-4">
                <div class="text-center">
                    <p class="text-2xl font-bold text-green-600 dark:text-green-400">
                        +{{ number_format($lastWeek['accuracy'] - $firstWeek['accuracy'], 1, ',', '.') }}%
                    </p>
                    <p class="text-xs text-gray-600 dark:text-gray-400">Verbesserung</p>
                </div>
                <div class="text-center">
                    <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                        {{ number_format($lastWeek['accuracy'], 1, ',', '.') }}%
                    </p>
                    <p class="text-xs text-gray-600 dark:text-gray-400">Aktuelle Genauigkeit</p>
                </div>
                <div class="text-center">
                    <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">
                        {{ number_format(array_sum(array_column($learningCurve, 'calls')), 0, ',', '.') }}
                    </p>
                    <p class="text-xs text-gray-600 dark:text-gray-400">Anrufe analysiert</p>
                </div>
            </div>
            @endif
        </div>
        @endif

        {{-- 7. Echtzeit-Metriken --}}
        @if(!empty($realtimeMetrics))
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            {{-- Warteschleifen-Analyse --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                    ⏱️ Warteschleifen-Analyse
                </h3>
                <div class="space-y-3">
                    <div>
                        <p class="text-xs text-gray-600 dark:text-gray-400">Ø Wartezeit</p>
                        <p class="text-2xl font-bold text-green-600 dark:text-green-400">
                            {{ number_format($realtimeMetrics['avg_wait_time'] ?? 0, 1, ',', '.') }} Sek
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-600 dark:text-gray-400">Ø Gesprächsdauer</p>
                        <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                            {{ number_format($realtimeMetrics['avg_call_duration'] ?? 0, 1, ',', '.') }} Min
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-600 dark:text-gray-400">Erstlösungsquote</p>
                        <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">
                            {{ number_format($realtimeMetrics['first_call_resolution'] ?? 0, 1, ',', '.') }}%
                        </p>
                    </div>
                </div>
            </div>

            {{-- Sentiment-Analyse --}}
            @if(isset($realtimeMetrics['sentiment']))
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                    😊 Sentiment-Analyse
                </h3>
                <div class="space-y-3">
                    <div class="flex items-center gap-2">
                        <span class="text-2xl">😊</span>
                        <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-4">
                            <div class="bg-green-500 h-4 rounded-full" style="width: {{ $realtimeMetrics['sentiment']['positive'] }}%"></div>
                        </div>
                        <span class="text-sm font-medium">{{ number_format($realtimeMetrics['sentiment']['positive'], 1, ',', '.') }}%</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-2xl">😐</span>
                        <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-4">
                            <div class="bg-yellow-500 h-4 rounded-full" style="width: {{ $realtimeMetrics['sentiment']['neutral'] }}%"></div>
                        </div>
                        <span class="text-sm font-medium">{{ number_format($realtimeMetrics['sentiment']['neutral'], 1, ',', '.') }}%</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-2xl">😞</span>
                        <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-4">
                            <div class="bg-red-500 h-4 rounded-full" style="width: {{ $realtimeMetrics['sentiment']['negative'] }}%"></div>
                        </div>
                        <span class="text-sm font-medium">{{ number_format($realtimeMetrics['sentiment']['negative'], 1, ',', '.') }}%</span>
                    </div>
                </div>
            </div>
            @endif

            {{-- Wiederanruf-Rate --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                    🔄 Wiederanruf-Rate
                </h3>
                <div class="relative pt-8">
                    <svg class="w-32 h-32 mx-auto transform -rotate-90">
                        <circle cx="64" cy="64" r="56" stroke="currentColor" stroke-width="8" fill="none" class="text-gray-200 dark:text-gray-700"></circle>
                        <circle cx="64" cy="64" r="56" stroke="currentColor" stroke-width="8" fill="none" 
                                stroke-dasharray="{{ 2 * 3.14159 * 56 }}" 
                                stroke-dashoffset="{{ 2 * 3.14159 * 56 * (1 - ($realtimeMetrics['callback_rate'] ?? 0) / 100) }}"
                                class="text-green-500 transition-all duration-500"></circle>
                    </svg>
                    <div class="absolute inset-0 flex items-center justify-center">
                        <div class="text-center">
                            <p class="text-3xl font-bold text-green-600 dark:text-green-400">
                                {{ number_format($realtimeMetrics['callback_rate'] ?? 0, 1, ',', '.') }}%
                            </p>
                            <p class="text-xs text-gray-600 dark:text-gray-400">Wiederanrufe</p>
                        </div>
                    </div>
                </div>
                <p class="text-xs text-center text-gray-500 dark:text-gray-400 mt-4">
                    {{ number_format(100 - ($realtimeMetrics['callback_rate'] ?? 0), 1, ',', '.') }}% beim ersten Anruf gelöst
                </p>
            </div>
        </div>
        @endif
    </div>
</x-filament-panels::page>