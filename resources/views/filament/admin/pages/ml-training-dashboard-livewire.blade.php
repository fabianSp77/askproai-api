@push('styles')
<link rel="stylesheet" href="{{ asset('css/ml-dashboard-premium.css') }}">
@endpush

<x-filament-panels::page>
    {{-- Premium Gradient Background --}}
    <div class="absolute inset-0 -z-10 overflow-hidden">
        <div class="absolute left-[max(50%,25rem)] top-0 h-[64rem] w-[128rem] -translate-x-1/2 stroke-gray-200 dark:stroke-gray-700 [mask-image:radial-gradient(64rem_64rem_at_top,white,transparent)]">
            <svg class="absolute inset-0 h-full w-full" aria-hidden="true">
                <defs>
                    <pattern id="ml-pattern" width="200" height="200" x="50%" y="-1" patternUnits="userSpaceOnUse">
                        <path d="M100 200V.5M.5 .5H200" fill="none"></path>
                    </pattern>
                </defs>
                <rect width="100%" height="100%" stroke-width="0" fill="url(#ml-pattern)"></rect>
            </svg>
        </div>
    </div>
    
    <div class="space-y-6" wire:poll.30s="refreshProgress">
        
        {{-- Instructions/Guide --}}
        @if($showInstructions)
            <div class="fi-section rounded-xl bg-blue-50 dark:bg-blue-900/20 shadow-sm ring-1 ring-blue-200 dark:ring-blue-800">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-blue-900 dark:text-blue-100 mb-4 flex items-center gap-2">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                        </svg>
                        Anleitung: So funktioniert das ML-System
                    </h3>
                    
                    <div class="space-y-4">
                        <div class="bg-white dark:bg-gray-800 rounded-lg p-4">
                            <h4 class="font-semibold text-blue-900 dark:text-blue-100 mb-2">
                                📋 Richtige Reihenfolge:
                            </h4>
                            <ol class="list-decimal list-inside space-y-2 text-sm text-blue-800 dark:text-blue-200">
                                <li>
                                    <strong>Zuerst: ML Modell trainieren</strong> (wenn genug Daten vorhanden)
                                    <ul class="ml-6 mt-1 list-disc text-blue-700 dark:text-blue-300">
                                        <li>Benötigt mindestens 10 Anrufe mit Transkript</li>
                                        <li>Erstellt ein Machine Learning Modell für präzise Sentiment-Analyse</li>
                                        <li>Dauert ca. 2-5 Minuten</li>
                                    </ul>
                                </li>
                                <li>
                                    <strong>Dann: Anrufe analysieren</strong>
                                    <ul class="ml-6 mt-1 list-disc text-blue-700 dark:text-blue-300">
                                        <li>Nutzt das trainierte ML-Modell für neue Analysen</li>
                                        <li>Falls kein Modell vorhanden: Regelbasierte Analyse (weniger genau)</li>
                                        <li>Analysiert nur Anrufe ohne bestehende Vorhersage</li>
                                    </ul>
                                </li>
                            </ol>
                        </div>
                        
                        <div class="bg-white dark:bg-gray-800 rounded-lg p-4">
                            <h4 class="font-semibold text-blue-900 dark:text-blue-100 mb-2">
                                @if($hasModel)
                                    ✅ Status: ML-Modell aktiv
                                @else
                                    ⚠️ Status: Kein ML-Modell trainiert
                                @endif
                            </h4>
                            <p class="text-sm text-blue-800 dark:text-blue-200">
                                @if($hasModel)
                                    Sie haben ein trainiertes Modell. Neue Anrufe werden mit ML analysiert.
                                    <br>Modell-Genauigkeit: {{ $modelInfo['accuracy'] ? round($modelInfo['accuracy'] * 100, 1) . '%' : 'N/A' }}
                                @else
                                    Trainieren Sie zuerst ein ML-Modell für beste Ergebnisse. 
                                    Alternativ können Sie mit regelbasierter Analyse beginnen.
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        @endif
        
        {{-- Active Jobs Progress --}}
        @if(count($activeJobs) > 0)
            <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="fi-section-header flex items-center gap-x-3 px-6 py-4">
                    <div class="grid flex-1 gap-y-1">
                        <h3 class="fi-section-header-heading text-base font-semibold leading-6 text-gray-950 dark:text-white">
                            Aktive Prozesse
                        </h3>
                    </div>
                    <div class="animate-spin h-5 w-5 text-primary-600">
                        <svg class="w-full h-full" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                </div>
                
                <div class="fi-section-content-ctn border-t border-gray-200 dark:border-white/10">
                    <div class="fi-section-content p-6 space-y-4">
                        @foreach($activeJobs as $job)
                            <div class="space-y-2">
                                <div class="flex justify-between items-center">
                                    <div>
                                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                            {{ $job['type'] === 'training' ? '🎓 ML Training' : '🔍 Analyse' }}
                                        </span>
                                        <span class="ml-2 text-sm text-gray-500 dark:text-gray-400">
                                            {{ $job['message'] ?? 'Läuft...' }}
                                        </span>
                                    </div>
                                    <span class="text-sm font-mono text-gray-600 dark:text-gray-400">
                                        {{ $job['progress'] }}%
                                    </span>
                                </div>
                                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5">
                                    <div class="bg-primary-600 h-2.5 rounded-full transition-all duration-500 ease-out"
                                         style="width: {{ $job['progress'] }}%">
                                    </div>
                                </div>
                                <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400">
                                    <span>Schritt: {{ $job['current_step'] ?? 'N/A' }}</span>
                                    <span>Laufzeit: {{ $job['duration'] }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
        
        {{-- Sentiment Distribution Chart --}}
        @if(count($sentimentDistribution) > 0)
            <div class="fi-section rounded-xl bg-gradient-to-br from-white to-gray-50 dark:from-gray-900 dark:to-gray-800 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
                <div class="fi-section-header flex items-center gap-x-3 px-6 py-4">
                    <div class="grid flex-1 gap-y-1">
                        <h3 class="fi-section-header-heading text-base font-semibold leading-6 text-gray-950 dark:text-white">
                            📊 Sentiment-Verteilung
                        </h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            Verteilung der analysierten Stimmungen
                        </p>
                    </div>
                </div>
                
                <div class="fi-section-content-ctn border-t border-gray-200 dark:border-white/10">
                    <div class="fi-section-content p-6">
                        <div class="grid grid-cols-3 gap-4">
                            @php
                                $total = array_sum($sentimentDistribution);
                                $colors = [
                                    'positive' => ['bg' => 'bg-green-500', 'text' => 'text-green-600'],
                                    'neutral' => ['bg' => 'bg-gray-500', 'text' => 'text-gray-600'],
                                    'negative' => ['bg' => 'bg-red-500', 'text' => 'text-red-600'],
                                ];
                            @endphp
                            @foreach(['positive', 'neutral', 'negative'] as $sentiment)
                                @php
                                    $count = $sentimentDistribution[$sentiment] ?? 0;
                                    $percentage = $total > 0 ? round(($count / $total) * 100, 1) : 0;
                                @endphp
                                <div class="text-center">
                                    <div class="relative inline-flex items-center justify-center w-24 h-24 mb-2">
                                        <svg class="w-24 h-24 transform -rotate-90">
                                            <circle cx="48" cy="48" r="36" stroke="currentColor" stroke-width="8" fill="none" class="text-gray-200 dark:text-gray-700"></circle>
                                            <circle cx="48" cy="48" r="36" stroke="currentColor" stroke-width="8" fill="none" 
                                                class="{{ $colors[$sentiment]['text'] }}"
                                                stroke-dasharray="{{ 226.19 * ($percentage / 100) }} 226.19"
                                                stroke-linecap="round"></circle>
                                        </svg>
                                        <span class="absolute text-xl font-bold">{{ $percentage }}%</span>
                                    </div>
                                    <p class="text-sm font-medium capitalize">{{ $sentiment }}</p>
                                    <p class="text-xs text-gray-500">{{ number_format($count) }} Anrufe</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        @endif
        
        {{-- Performance Trend Chart --}}
        @if(count($performanceMetrics) > 0)
            <div class="fi-section rounded-xl bg-gradient-to-br from-white to-gray-50 dark:from-gray-900 dark:to-gray-800 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
                <div class="fi-section-header flex items-center gap-x-3 px-6 py-4">
                    <div class="grid flex-1 gap-y-1">
                        <h3 class="fi-section-header-heading text-base font-semibold leading-6 text-gray-950 dark:text-white">
                            📈 ML Performance Trend
                        </h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            Durchschnittliche Konfidenz der letzten 7 Tage
                        </p>
                    </div>
                </div>
                
                <div class="fi-section-content-ctn border-t border-gray-200 dark:border-white/10">
                    <div class="fi-section-content p-6">
                        <div class="h-48" id="performance-chart"></div>
                    </div>
                </div>
            </div>
        @endif
        
        {{-- Overview Stats --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- Total Calls --}}
            <div class="group fi-wi-stats-overview-stat relative rounded-xl bg-gradient-to-br from-blue-50 to-white dark:from-blue-950/50 dark:to-gray-900 p-6 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 hover:shadow-lg transition-all duration-200">
                <div class="absolute top-4 right-4 p-2 rounded-lg bg-blue-100 dark:bg-blue-900/50 group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                    </svg>
                </div>
                <div class="flex flex-col">
                    <span class="text-sm font-medium text-gray-600 dark:text-gray-400">
                        Gesamte Anrufe
                    </span>
                    <div class="text-3xl font-bold tracking-tight text-gray-950 dark:text-white mt-1">
                        {{ number_format($trainingStats['total_calls']) }}
                    </div>
                    <div class="flex items-center gap-1 mt-2">
                        <span class="text-xs text-gray-500 dark:text-gray-400">
                            {{ $trainingStats['calls_with_audio'] }} mit Audio
                        </span>
                    </div>
                </div>
            </div>
            
            {{-- Calls with Transcript --}}
            <div class="group fi-wi-stats-overview-stat relative rounded-xl bg-gradient-to-br from-purple-50 to-white dark:from-purple-950/50 dark:to-gray-900 p-6 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 hover:shadow-lg transition-all duration-200">
                <div class="absolute top-4 right-4 p-2 rounded-lg bg-purple-100 dark:bg-purple-900/50 group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
                <div class="flex flex-col">
                    <span class="text-sm font-medium text-gray-600 dark:text-gray-400">
                        Mit Transkript
                    </span>
                    <div class="text-3xl font-bold tracking-tight text-gray-950 dark:text-white mt-1">
                        {{ number_format($trainingStats['calls_with_transcript']) }}
                    </div>
                    <div class="flex items-center gap-1 mt-2">
                        @if($trainingStats['calls_with_transcript'] < 10)
                            <svg class="w-4 h-4 text-warning-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="text-xs text-warning-600 dark:text-warning-400 font-medium">
                                Min. 10 für Training
                            </span>
                        @else
                            <div class="flex items-center gap-2 w-full">
                                <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-1.5">
                                    <div class="bg-purple-600 h-1.5 rounded-full" style="width: {{ round(($trainingStats['calls_with_transcript'] / max($trainingStats['total_calls'], 1)) * 100, 1) }}%"></div>
                                </div>
                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ round(($trainingStats['calls_with_transcript'] / max($trainingStats['total_calls'], 1)) * 100, 1) }}%
                                </span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            
            {{-- Analyzed Calls --}}
            <div class="group fi-wi-stats-overview-stat relative rounded-xl bg-gradient-to-br from-green-50 to-white dark:from-green-950/50 dark:to-gray-900 p-6 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 hover:shadow-lg transition-all duration-200">
                <div class="absolute top-4 right-4 p-2 rounded-lg bg-green-100 dark:bg-green-900/50 group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
                <div class="flex flex-col">
                    <span class="text-sm font-medium text-gray-600 dark:text-gray-400">
                        Analysiert
                    </span>
                    <div class="text-3xl font-bold tracking-tight text-gray-950 dark:text-white mt-1">
                        {{ number_format($trainingStats['calls_with_predictions']) }}
                    </div>
                    <div class="flex items-center gap-1 mt-2">
                        @if($trainingStats['calls_without_predictions'] > 0)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-200">
                                {{ $trainingStats['calls_without_predictions'] }} ausstehend
                            </span>
                        @else
                            <span class="text-xs text-green-600 dark:text-green-400 font-medium">
                                ✓ Alle analysiert
                            </span>
                        @endif
                    </div>
                </div>
            </div>
            
            {{-- Model Status --}}
            <div class="group fi-wi-stats-overview-stat relative rounded-xl bg-gradient-to-br @if($modelInfo) from-emerald-50 to-white dark:from-emerald-950/50 @else from-red-50 to-white dark:from-red-950/50 @endif dark:to-gray-900 p-6 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 hover:shadow-lg transition-all duration-200">
                <div class="absolute top-4 right-4 p-2 rounded-lg @if($modelInfo) bg-emerald-100 dark:bg-emerald-900/50 @else bg-red-100 dark:bg-red-900/50 @endif group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6 @if($modelInfo) text-emerald-600 dark:text-emerald-400 @else text-red-600 dark:text-red-400 @endif" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <div class="flex flex-col">
                    <span class="text-sm font-medium text-gray-600 dark:text-gray-400">
                        ML Modell
                    </span>
                    <div class="text-2xl font-bold tracking-tight mt-1">
                        @if($modelInfo)
                            <span class="text-emerald-600 dark:text-emerald-400">Aktiv</span>
                        @else
                            <span class="text-red-600 dark:text-red-400">Inaktiv</span>
                        @endif
                    </div>
                    <div class="flex items-center gap-1 mt-2">
                        @if($modelInfo)
                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                v{{ $modelInfo['version'] }} • {{ $modelInfo['accuracy'] ? round($modelInfo['accuracy'] * 100, 1) . '% Genauigkeit' : 'Training läuft' }}
                            </span>
                        @else
                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                Training erforderlich
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        
        {{-- Model Information --}}
        @if($modelInfo)
            <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="fi-section-header flex items-center gap-x-3 px-6 py-4">
                    <div class="grid flex-1 gap-y-1">
                        <h3 class="fi-section-header-heading text-base font-semibold leading-6 text-gray-950 dark:text-white">
                            Aktuelles ML Modell
                        </h3>
                    </div>
                </div>
                
                <div class="fi-section-content-ctn border-t border-gray-200 dark:border-white/10">
                    <div class="fi-section-content p-6">
                        <dl class="grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-2 lg:grid-cols-4">
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Version</dt>
                                <dd class="mt-1 text-sm text-gray-950 dark:text-white">{{ $modelInfo['version'] }}</dd>
                            </div>
                            @if($modelInfo['accuracy'])
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Genauigkeit</dt>
                                    <dd class="mt-1 text-sm text-gray-950 dark:text-white">{{ round($modelInfo['accuracy'] * 100, 1) }}%</dd>
                                </div>
                            @endif
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Training Samples</dt>
                                <dd class="mt-1 text-sm text-gray-950 dark:text-white">{{ number_format($modelInfo['training_samples']) }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Trainiert am</dt>
                                <dd class="mt-1 text-sm text-gray-950 dark:text-white">{{ $modelInfo['created_at']->format('d.m.Y H:i') }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>
        @endif
        
        {{-- Recent Predictions --}}
        @if(count($recentPredictions) > 0)
            <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="fi-section-header flex items-center gap-x-3 px-6 py-4">
                    <div class="grid flex-1 gap-y-1">
                        <h3 class="fi-section-header-heading text-base font-semibold leading-6 text-gray-950 dark:text-white">
                            Aktuelle Vorhersagen
                        </h3>
                    </div>
                </div>
                
                <div class="fi-section-content-ctn border-t border-gray-200 dark:border-white/10">
                    <div class="fi-section-content">
                        <div class="overflow-x-auto">
                            <table class="w-full divide-y divide-gray-200 dark:divide-white/5">
                                <thead>
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                            Anrufer
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                            Sentiment
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                            Score
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                            Konfidenz
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                            Zeitpunkt
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                                    @foreach($recentPredictions as $prediction)
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                                            <td class="px-6 py-4">
                                                <div class="flex items-center">
                                                    <div class="flex-shrink-0 h-10 w-10 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center">
                                                        <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                                        </svg>
                                                    </div>
                                                    <div class="ml-4">
                                                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                            {{ $prediction['phone'] }}
                                                        </div>
                                                        <div class="text-xs text-gray-500">
                                                            Anruf #{{ $prediction['call_id'] }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="flex items-center gap-2">
                                                    @if($prediction['sentiment'] === 'positive')
                                                        <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400">
                                                            😊 Positiv
                                                        </span>
                                                    @elseif($prediction['sentiment'] === 'negative')
                                                        <div class="w-2 h-2 bg-red-500 rounded-full animate-pulse"></div>
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400">
                                                            😞 Negativ
                                                        </span>
                                                    @else
                                                        <div class="w-2 h-2 bg-gray-500 rounded-full"></div>
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                                            😐 Neutral
                                                        </span>
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="flex items-center">
                                                    <div class="w-16 bg-gray-200 dark:bg-gray-700 rounded-full h-2 mr-2">
                                                        <div class="@if($prediction['score'] > 0) bg-green-500 @else bg-red-500 @endif h-2 rounded-full" 
                                                            style="width: {{ abs($prediction['score']) * 50 + 50 }}%">
                                                        </div>
                                                    </div>
                                                    <span class="text-sm font-medium text-gray-900 dark:text-white">
                                                        {{ number_format($prediction['score'], 2) }}
                                                    </span>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="flex items-center gap-2">
                                                    <svg class="w-4 h-4 @if($prediction['confidence'] >= 80) text-green-500 @elseif($prediction['confidence'] >= 60) text-yellow-500 @else text-red-500 @endif" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                                    </svg>
                                                    <span class="text-sm font-semibold @if($prediction['confidence'] >= 80) text-green-600 dark:text-green-400 @elseif($prediction['confidence'] >= 60) text-yellow-600 dark:text-yellow-400 @else text-red-600 dark:text-red-400 @endif">
                                                        {{ $prediction['confidence'] }}%
                                                    </span>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                                <div class="flex items-center gap-1">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                    </svg>
                                                    {{ $prediction['created_at'] }}
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
    
    @script
    <script>
        // Auto-refresh progress while jobs are active
        if (@js(count($activeJobs)) > 0) {
            setInterval(() => {
                $wire.refreshProgress();
            }, 30000);
        }
        
        // Performance Chart
        @if(count($performanceMetrics) > 0)
        (function() {
            const chartData = @js($performanceMetrics);
            const chartContainer = document.getElementById('performance-chart');
            
            if (chartContainer && chartData.length > 0) {
                // Simple SVG chart
                const width = chartContainer.offsetWidth;
                const height = 192;
                const padding = 40;
                
                const maxConfidence = Math.max(...chartData.map(d => d.avg_confidence || 0));
                const minConfidence = Math.min(...chartData.map(d => d.avg_confidence || 0));
                
                const xScale = (i) => (i / (chartData.length - 1)) * (width - 2 * padding) + padding;
                const yScale = (val) => height - ((val - minConfidence) / (maxConfidence - minConfidence) * (height - 2 * padding) + padding);
                
                // Create SVG
                const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
                svg.setAttribute('width', width);
                svg.setAttribute('height', height);
                svg.setAttribute('class', 'w-full h-full');
                
                // Grid lines
                for (let i = 0; i <= 4; i++) {
                    const y = padding + (i / 4) * (height - 2 * padding);
                    const line = document.createElementNS('http://www.w3.org/2000/svg', 'line');
                    line.setAttribute('x1', padding);
                    line.setAttribute('y1', y);
                    line.setAttribute('x2', width - padding);
                    line.setAttribute('y2', y);
                    line.setAttribute('stroke', 'rgb(229 231 235)');
                    line.setAttribute('stroke-dasharray', '2 2');
                    svg.appendChild(line);
                }
                
                // Path
                const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
                let pathData = `M ${xScale(0)} ${yScale(chartData[0].avg_confidence)}`;
                
                for (let i = 1; i < chartData.length; i++) {
                    pathData += ` L ${xScale(i)} ${yScale(chartData[i].avg_confidence)}`;
                }
                
                path.setAttribute('d', pathData);
                path.setAttribute('fill', 'none');
                path.setAttribute('stroke', 'rgb(99 102 241)');
                path.setAttribute('stroke-width', '3');
                svg.appendChild(path);
                
                // Dots
                chartData.forEach((d, i) => {
                    const circle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
                    circle.setAttribute('cx', xScale(i));
                    circle.setAttribute('cy', yScale(d.avg_confidence));
                    circle.setAttribute('r', '4');
                    circle.setAttribute('fill', 'rgb(99 102 241)');
                    circle.setAttribute('class', 'hover:r-6 transition-all cursor-pointer');
                    
                    // Tooltip
                    const title = document.createElementNS('http://www.w3.org/2000/svg', 'title');
                    title.textContent = `${d.date}: ${Math.round(d.avg_confidence)}% (${d.predictions} Vorhersagen)`;
                    circle.appendChild(title);
                    
                    svg.appendChild(circle);
                });
                
                // Labels
                chartData.forEach((d, i) => {
                    if (i === 0 || i === chartData.length - 1) {
                        const text = document.createElementNS('http://www.w3.org/2000/svg', 'text');
                        text.setAttribute('x', xScale(i));
                        text.setAttribute('y', height - 10);
                        text.setAttribute('text-anchor', 'middle');
                        text.setAttribute('class', 'text-xs fill-gray-500');
                        text.textContent = new Date(d.date).toLocaleDateString('de-DE', { month: 'short', day: 'numeric' });
                        svg.appendChild(text);
                    }
                });
                
                chartContainer.appendChild(svg);
            }
        })();
        @endif
    </script>
    @endscript
</x-filament-panels::page>