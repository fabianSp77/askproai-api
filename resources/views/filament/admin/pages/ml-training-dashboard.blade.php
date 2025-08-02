<x-filament-panels::page>
    {{-- CSS for premium styling --}}
    <link rel="stylesheet" href="{{ asset('css/ml-dashboard-premium.css') }}">
    
    <div wire:poll.30s="refreshProgress">
        {{-- Instructions Section --}}
        @if($showInstructions)
        <div class="mb-6 p-4 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-gray-800 dark:to-gray-700 rounded-lg border border-blue-200 dark:border-gray-600">
            <h3 class="text-lg font-semibold text-blue-900 dark:text-blue-100 mb-2">
                <x-heroicon-o-light-bulb class="w-5 h-5 inline mr-2"/>
                ML Training Anleitung
            </h3>
            <p class="text-blue-700 dark:text-blue-200 mb-3">
                Das ML-System analysiert Anrufe und lernt, Kundenstimmungen zu erkennen. 
                Es kann positive, neutrale und negative Gesprächsverläufe identifizieren.
            </p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <h4 class="font-medium text-blue-800 dark:text-blue-100">Voraussetzungen:</h4>
                    <ul class="text-sm text-blue-600 dark:text-blue-300 space-y-1 mt-1">
                        <li>• Mindestens 10 Anrufe mit Transkript</li>
                        <li>• Idealerweise mit Audio-Aufzeichnung</li>
                        <li>• Verschiedene Gesprächstypen</li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-medium text-blue-800 dark:text-blue-100">Workflow:</h4>
                    <ol class="text-sm text-blue-600 dark:text-blue-300 space-y-1 mt-1">
                        <li>1. ML Modell trainieren (einmalig)</li>
                        <li>2. Neue Anrufe automatisch analysieren</li>
                        <li>3. Ergebnisse in Berichten nutzen</li>
                    </ol>
                </div>
            </div>
        </div>
        @endif

        {{-- Stats Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            {{-- Total Calls Card --}}
            <div class="stats-card gradient-blue">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-300">Gesamte Anrufe</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($trainingStats['total_calls']) }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            {{ number_format($trainingStats['calls_with_audio']) }} mit Audio
                        </p>
                    </div>
                    <div class="stats-icon">
                        <x-heroicon-o-phone class="w-8 h-8 text-blue-500"/>
                    </div>
                </div>
            </div>

            {{-- Calls with Transcript Card --}}
            <div class="stats-card gradient-green">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-300">Mit Transkript</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($trainingStats['calls_with_transcript']) }}</p>
                        <div class="mt-2">
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: {{ $trainingStats['total_calls'] > 0 ? round(($trainingStats['calls_with_transcript'] / $trainingStats['total_calls']) * 100) : 0 }}%"></div>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                {{ $trainingStats['total_calls'] > 0 ? round(($trainingStats['calls_with_transcript'] / $trainingStats['total_calls']) * 100) : 0 }}% verfügbar
                            </p>
                        </div>
                    </div>
                    <div class="stats-icon">
                        <x-heroicon-o-document-text class="w-8 h-8 text-green-500"/>
                    </div>
                </div>
            </div>

            {{-- Analyzed Calls Card --}}
            <div class="stats-card gradient-purple">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-300">Analysiert</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($trainingStats['calls_with_predictions']) }}</p>
                        @if($trainingStats['calls_without_predictions'] > 0)
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100 mt-1">
                            {{ number_format($trainingStats['calls_without_predictions']) }} ausstehend
                        </span>
                        @endif
                    </div>
                    <div class="stats-icon">
                        <x-heroicon-o-sparkles class="w-8 h-8 text-purple-500"/>
                    </div>
                </div>
            </div>

            {{-- Model Status Card --}}
            <div class="stats-card {{ $modelInfo ? 'gradient-success' : 'gradient-gray' }}">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-300">ML Modell</p>
                        @if($modelInfo)
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">Aktiv</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                v{{ $modelInfo['version'] }} • {{ number_format($modelInfo['accuracy'] * 100, 1) }}% Genauigkeit
                            </p>
                        @else
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">Nicht trainiert</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Training erforderlich</p>
                        @endif
                    </div>
                    <div class="stats-icon">
                        <x-heroicon-o-cpu-chip class="w-8 h-8 {{ $modelInfo ? 'text-green-500' : 'text-gray-400' }}"/>
                    </div>
                </div>
            </div>
        </div>

        {{-- Sentiment Distribution & Performance Charts --}}
        @if(count($sentimentDistribution) > 0 || count($performanceMetrics) > 0)
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            {{-- Sentiment Distribution --}}
            @if(array_sum($sentimentDistribution) > 0)
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Sentiment-Verteilung</h3>
                <div class="space-y-4">
                    @php
                        $total = array_sum($sentimentDistribution);
                    @endphp
                    @foreach(['positive' => ['😊', 'bg-green-500'], 'neutral' => ['😐', 'bg-gray-400'], 'negative' => ['😞', 'bg-red-500']] as $sentiment => [$emoji, $color])
                        @php
                            $count = $sentimentDistribution[$sentiment] ?? 0;
                            $percentage = $total > 0 ? round(($count / $total) * 100) : 0;
                        @endphp
                        <div>
                            <div class="flex justify-between items-center mb-1">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                    {{ $emoji }} {{ ucfirst($sentiment) }}
                                </span>
                                <span class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ number_format($count) }} ({{ $percentage }}%)
                                </span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                <div class="{{ $color }} h-2 rounded-full transition-all duration-500" style="width: {{ $percentage }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Performance Trend --}}
            @if(count($performanceMetrics) > 0)
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Performance Trend (7 Tage)</h3>
                <div class="relative h-48">
                    <svg class="w-full h-full" viewBox="0 0 400 200">
                        @php
                            $maxConfidence = max(array_column($performanceMetrics, 'avg_confidence'));
                            $points = [];
                            foreach($performanceMetrics as $index => $metric) {
                                $x = ($index / (count($performanceMetrics) - 1)) * 380 + 10;
                                $y = 190 - (($metric['avg_confidence'] / max($maxConfidence, 1)) * 180);
                                $points[] = "$x,$y";
                            }
                        @endphp
                        
                        {{-- Grid lines --}}
                        @for($i = 0; $i <= 4; $i++)
                            <line x1="10" y1="{{ 10 + $i * 45 }}" x2="390" y2="{{ 10 + $i * 45 }}" stroke="#e5e7eb" stroke-width="1"/>
                        @endfor
                        
                        {{-- Line chart --}}
                        @if(count($points) > 1)
                            <polyline points="{{ implode(' ', $points) }}" fill="none" stroke="#3b82f6" stroke-width="2"/>
                            
                            {{-- Data points --}}
                            @foreach($points as $point)
                                @php list($x, $y) = explode(',', $point); @endphp
                                <circle cx="{{ $x }}" cy="{{ $y }}" r="4" fill="#3b82f6"/>
                            @endforeach
                        @endif
                    </svg>
                </div>
            </div>
            @endif
        </div>
        @endif

        {{-- Active Jobs --}}
        @if(count($activeJobs) > 0)
        <div class="mb-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Aktive Jobs</h3>
            <div class="space-y-3">
                @foreach($activeJobs as $job)
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4">
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center">
                            <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-500 mr-3"></div>
                            <span class="font-medium text-gray-900 dark:text-white">
                                {{ $job['type'] === 'training' ? 'ML Training' : 'Sentiment Analyse' }}
                            </span>
                            <span class="ml-2 text-sm text-gray-500 dark:text-gray-400">
                                #{{ $job['job_id'] }}
                            </span>
                        </div>
                        <span class="text-sm text-gray-500 dark:text-gray-400">
                            {{ $job['duration'] }}
                        </span>
                    </div>
                    <div class="mb-2">
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-gray-600 dark:text-gray-300">{{ $job['message'] ?? 'Processing...' }}</span>
                            <span class="text-gray-600 dark:text-gray-300">{{ number_format($job['progress']) }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                            <div class="bg-blue-500 h-2 rounded-full transition-all duration-500" style="width: {{ $job['progress'] }}%"></div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Recent Predictions --}}
        @if(count($recentPredictions) > 0)
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Kürzliche Analysen</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Anrufer</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Sentiment</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Score</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Konfidenz</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Zeit</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($recentPredictions as $prediction)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10">
                                        <div class="h-10 w-10 rounded-full bg-gray-300 dark:bg-gray-600 flex items-center justify-center">
                                            <x-heroicon-o-user class="h-6 w-6 text-gray-500 dark:text-gray-400"/>
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ $prediction['phone'] }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php
                                    $sentimentConfig = [
                                        'positive' => ['😊', 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100'],
                                        'neutral' => ['😐', 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-100'],
                                        'negative' => ['😞', 'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100']
                                    ];
                                    $config = $sentimentConfig[$prediction['sentiment']] ?? $sentimentConfig['neutral'];
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $config[1] }}">
                                    {{ $config[0] }} {{ ucfirst($prediction['sentiment']) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="w-16 bg-gray-200 dark:bg-gray-700 rounded-full h-2 mr-2">
                                        @php
                                            $normalizedScore = ($prediction['score'] + 1) / 2 * 100;
                                            $barColor = $prediction['score'] > 0.3 ? 'bg-green-500' : ($prediction['score'] < -0.3 ? 'bg-red-500' : 'bg-gray-400');
                                        @endphp
                                        <div class="{{ $barColor }} h-2 rounded-full" style="width: {{ $normalizedScore }}%"></div>
                                    </div>
                                    <span class="text-sm text-gray-900 dark:text-white">{{ number_format($prediction['score'], 2) }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php
                                    $confidenceValue = intval(str_replace('%', '', $prediction['confidence']));
                                    $confidenceColor = $confidenceValue >= 80 ? 'text-green-600 dark:text-green-400' : ($confidenceValue >= 60 ? 'text-yellow-600 dark:text-yellow-400' : 'text-red-600 dark:text-red-400');
                                @endphp
                                <span class="text-sm font-medium {{ $confidenceColor }}">
                                    {{ $prediction['confidence'] }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                <div class="flex items-center">
                                    <x-heroicon-o-clock class="h-4 w-4 mr-1"/>
                                    {{ $prediction['created_at'] }}
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>
</x-filament-panels::page>