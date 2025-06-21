@props([
    'currentRoi' => 0,
    'industryAvg' => 150,
    'topPerformer' => 300,
    'percentile' => 50,
])

<div {{ $attributes->merge(['class' => 'rounded-xl bg-white p-6 shadow-sm']) }}>
    <h3 class="mb-4 text-lg font-semibold text-gray-900">ROI-Benchmark</h3>
    
    {{-- Visual Benchmark Scale --}}
    <div class="relative mb-8">
        {{-- Scale Background --}}
        <div class="h-8 w-full overflow-hidden rounded-full bg-gradient-to-r from-red-100 via-yellow-100 to-green-100">
            <div class="absolute inset-0 flex items-center justify-between px-2 text-xs text-gray-600">
                <span>0%</span>
                <span>{{ $industryAvg }}%</span>
                <span>{{ $topPerformer }}%+</span>
            </div>
        </div>
        
        {{-- Your Position Marker --}}
        <div class="absolute -top-2 h-12 w-1 bg-blue-600 shadow-lg transition-all duration-500"
             style="left: {{ min(($currentRoi / $topPerformer) * 100, 95) }}%">
            <div class="absolute -top-8 left-1/2 -translate-x-1/2 whitespace-nowrap rounded-lg bg-blue-600 px-2 py-1 text-xs font-medium text-white">
                Ihr ROI: {{ number_format($currentRoi, 1) }}%
                <div class="absolute left-1/2 top-full -mt-1 h-2 w-2 -translate-x-1/2 rotate-45 transform bg-blue-600"></div>
            </div>
        </div>
        
        {{-- Industry Average Marker --}}
        <div class="absolute top-0 h-8 w-0.5 bg-gray-400"
             style="left: {{ ($industryAvg / $topPerformer) * 100 }}%">
        </div>
    </div>
    
    {{-- Detailed Comparison --}}
    <div class="space-y-3">
        {{-- Your Performance --}}
        <div class="flex items-center justify-between rounded-lg bg-blue-50 p-3">
            <div class="flex items-center space-x-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100">
                    <x-heroicon-o-building-office class="h-5 w-5 text-blue-600" />
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-900">Ihre Performance</p>
                    <p class="text-xs text-gray-500">{{ $percentile }}. Perzentil</p>
                </div>
            </div>
            <div class="text-right">
                <p class="text-lg font-bold 
                    @if($currentRoi >= $topPerformer) text-green-600
                    @elseif($currentRoi >= $industryAvg) text-blue-600
                    @elseif($currentRoi >= 0) text-yellow-600
                    @else text-red-600
                    @endif">
                    {{ number_format($currentRoi, 1) }}%
                </p>
                <p class="text-xs text-gray-500">ROI</p>
            </div>
        </div>
        
        {{-- Industry Average --}}
        <div class="flex items-center justify-between rounded-lg bg-gray-50 p-3">
            <div class="flex items-center space-x-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-gray-100">
                    <x-heroicon-o-chart-bar class="h-5 w-5 text-gray-600" />
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-900">Branchendurchschnitt</p>
                    <p class="text-xs text-gray-500">Alle Unternehmen</p>
                </div>
            </div>
            <div class="text-right">
                <p class="text-lg font-bold text-gray-600">{{ number_format($industryAvg, 1) }}%</p>
                <p class="text-xs text-gray-500">ROI</p>
            </div>
        </div>
        
        {{-- Top Performers --}}
        <div class="flex items-center justify-between rounded-lg bg-green-50 p-3">
            <div class="flex items-center space-x-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-green-100">
                    <x-heroicon-o-trophy class="h-5 w-5 text-green-600" />
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-900">Top-Performer</p>
                    <p class="text-xs text-gray-500">Top 10%</p>
                </div>
            </div>
            <div class="text-right">
                <p class="text-lg font-bold text-green-600">{{ number_format($topPerformer, 1) }}%+</p>
                <p class="text-xs text-gray-500">ROI</p>
            </div>
        </div>
    </div>
    
    {{-- Performance Message --}}
    <div class="mt-4 rounded-lg 
        @if($currentRoi >= $topPerformer) bg-green-100 border-green-200
        @elseif($currentRoi >= $industryAvg) bg-blue-100 border-blue-200
        @elseif($currentRoi >= 0) bg-yellow-100 border-yellow-200
        @else bg-red-100 border-red-200
        @endif
        border p-3">
        <p class="text-sm">
            @if($currentRoi >= $topPerformer)
                <span class="font-medium text-green-800">Hervorragend!</span>
                <span class="text-green-700">Sie gehören zu den Top-Performern Ihrer Branche.</span>
            @elseif($currentRoi >= $industryAvg)
                <span class="font-medium text-blue-800">Gut gemacht!</span>
                <span class="text-blue-700">Sie liegen über dem Branchendurchschnitt.</span>
            @elseif($currentRoi >= 0)
                <span class="font-medium text-yellow-800">Verbesserungspotenzial vorhanden.</span>
                <span class="text-yellow-700">Sie liegen unter dem Branchendurchschnitt.</span>
            @else
                <span class="font-medium text-red-800">Handlungsbedarf!</span>
                <span class="text-red-700">Ihr ROI ist negativ. Optimierungen dringend empfohlen.</span>
            @endif
        </p>
    </div>
    
    {{-- Action Button --}}
    <div class="mt-4">
        <button class="w-full rounded-lg bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-200">
            Optimierungsvorschläge anzeigen
        </button>
    </div>
</div>