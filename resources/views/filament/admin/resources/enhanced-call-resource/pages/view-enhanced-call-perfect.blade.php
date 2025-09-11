@php
    use App\Helpers\GermanFormatter;
@endphp

<x-filament-panels::page>
    @php
        $call = $record ?? $this->record ?? null;
        if (!$call) {
            throw new \Exception('No call record available');
        }
        
        // Load all 100+ fields with proper null checking
        $analysis = $call->analysis ?? [];
        $customData = $analysis['custom_analysis_data'] ?? [];
        $latencyMetrics = json_decode($call->latency_metrics ?? '{}', true);
        $costBreakdown = json_decode($call->cost_breakdown ?? '{}', true);
        $webhookData = json_decode($call->webhook_data ?? '{}', true);
        
        // Tier 1: Critical Data (0-5 seconds scan)
        $callStatus = $call->call_status ?? 'unknown';
        $isSuccessful = $call->call_successful ?? false;
        $duration = $call->duration_sec ?? 0;
        $durationFormatted = GermanFormatter::formatDuration($duration);
        $costCents = $call->cost_cents ?? $call->retell_cost * 100 ?? 0;
        $costFormatted = GermanFormatter::formatCentsToEuro($costCents);
        
        // Customer Intelligence
        $callerName = $customData['caller_full_name'] ?? $call->extracted_name ?? 'Unknown';
        $companyName = $customData['company_name'] ?? $call->notes['company'] ?? 'N/A';
        $customerNumber = $customData['customer_number'] ?? null;
        $urgencyLevel = $customData['urgency_level'] ?? $call->urgency_level ?? 'normal';
        
        // AI Analysis
        $sentiment = $call->sentiment ?? $analysis['sentiment'] ?? 'neutral';
        $summary = $call->summary ?? $call->call_summary ?? $analysis['call_summary'] ?? 'No summary available';
        $transcript = $call->transcript ?? '';
        $transcriptObject = json_decode($call->transcript_object ?? '[]', true);
        
        // Performance Metrics
        $latencyP50 = $latencyMetrics['llm']['p50'] ?? 0;
        $latencyP90 = $latencyMetrics['llm']['p90'] ?? 0;
        $latencyP95 = $latencyMetrics['llm']['p95'] ?? 0;
        $latencyP99 = $latencyMetrics['llm']['p99'] ?? 0;
        $tokenUsage = $call->llm_token_usage ?? $analysis['token_usage'] ?? 0;
        
        // Audio & Media
        $recordingUrl = $call->recording_url ?? $call->audio_url ?? null;
        $publicLogUrl = $call->public_log_url ?? null;
        
        // Language Detection
        $detectedLanguage = $call->detected_language ?? 'unknown';
        $languageConfidence = $call->language_confidence ?? 0;
        
        // Healthcare/Appointment Data
        $appointmentRequested = $call->appointment_requested ?? false;
        $calcomBookingId = $call->calcom_booking_id ?? null;
        
        // Technical Metadata
        $retellCallId = $call->retell_call_id ?? $call->call_id ?? null;
        $agentId = $call->agent_id ?? $call->retell_agent_id ?? null;
        $agentVersion = $call->agent_version ?? 0;
        
        // Determine user persona for adaptive UI
        $user = auth()->user();
        $userRole = $user ? ($user->role ?? 'agent') : 'agent';
        $isAdmin = $user ? $user->hasRole('admin') : false;
        $isManager = $user ? $user->hasRole('manager') : false;
        
        // Urgency and sentiment styling
        $urgencyColor = match($urgencyLevel) {
            'dringend', 'urgent' => 'red',
            'schnell', 'fast' => 'orange',
            'normal' => 'blue',
            default => 'gray'
        };
        
        $sentimentIcon = match(strtolower($sentiment)) {
            'positive' => '😊',
            'negative' => '😔',
            'neutral' => '😐',
            default => '🤔'
        };
    @endphp

    {{-- TIER 1: CRITICAL OVERVIEW (0-5 seconds scan) --}}
    <div class="tier-1-critical bg-white dark:bg-gray-800 rounded-xl shadow-lg p-4 sm:p-6 mb-6">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            {{-- Left: Status & Core Metrics --}}
            <div class="flex flex-col sm:flex-row flex-wrap items-start sm:items-center gap-4 sm:gap-6 lg:gap-8">
                {{-- Date & Time --}}
                <div class="flex flex-col items-center sm:items-start">
                    <div class="text-xs text-gray-500 uppercase tracking-wider mb-1">{{ __('enhanced-calls.date') }}</div>
                    <div class="text-sm font-semibold text-gray-900 dark:text-white whitespace-nowrap">{{ GermanFormatter::formatDateTime($call->created_at, true, false) }}</div>
                </div>
                {{-- Call Status Badge --}}
                <div class="flex flex-col items-center sm:items-start">
                    <div class="text-xs text-gray-500 uppercase tracking-wider mb-1">{{ __('enhanced-calls.status') }}</div>
                    @if($isSuccessful)
                        <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-bold bg-green-100 text-green-800">
                            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            {{ __('enhanced-calls.status_success') }}
                        </span>
                    @else
                        <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-bold bg-red-100 text-red-800">
                            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                            {{ __('enhanced-calls.status_failed') }}
                        </span>
                    @endif
                </div>

                {{-- Duration --}}
                <div class="flex flex-col items-center sm:items-start">
                    <div class="text-xs text-gray-500 uppercase tracking-wider mb-1">{{ __('enhanced-calls.duration') }}</div>
                    <div class="flex items-center">
                        <svg class="w-4 h-4 sm:w-5 sm:h-5 mr-1 sm:mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="text-lg sm:text-2xl font-bold text-gray-900 dark:text-white">{{ $durationFormatted }}</span>
                    </div>
                </div>

                {{-- Cost --}}
                <div class="flex flex-col items-center sm:items-start">
                    <div class="text-xs text-gray-500 uppercase tracking-wider mb-1">{{ __('enhanced-calls.cost') }}</div>
                    <div class="flex items-center">
                        <span class="text-lg sm:text-2xl font-bold text-gray-900 dark:text-white">{{ $costFormatted }}</span>
                    </div>
                </div>

                {{-- Sentiment --}}
                <div class="flex flex-col items-center sm:items-start">
                    <div class="text-xs text-gray-500 uppercase tracking-wider mb-1">{{ __('enhanced-calls.sentiment') }}</div>
                    <div class="text-2xl sm:text-3xl">{{ GermanFormatter::formatSentiment($sentiment)['icon'] }}</div>
                    <span class="text-xs text-gray-600">{{ GermanFormatter::formatSentiment($sentiment)['text'] }}</span>
                </div>

                {{-- Urgency (if urgent) --}}
                @if($urgencyLevel !== 'normal')
                    <div class="flex flex-col items-center sm:items-start">
                        <div class="text-xs text-gray-500 uppercase tracking-wider mb-1">{{ __('enhanced-calls.priority') }}</div>
                        <span class="inline-flex items-center px-2 sm:px-3 py-1 rounded-full text-xs sm:text-sm font-bold bg-{{ $urgencyColor }}-100 text-{{ $urgencyColor }}-800 animate-pulse">
                            ⚡ {{ strtoupper($urgencyLevel) }}
                        </span>
                    </div>
                @endif
            </div>

            {{-- Right: Quick Actions --}}
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 sm:gap-3 w-full sm:w-auto">
                @if($recordingUrl)
                    <button onclick="playAudio('{{ $recordingUrl }}')" 
                            class="w-full sm:w-auto inline-flex items-center justify-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="truncate">{{ __('enhanced-calls.play_recording') }}</span>
                    </button>
                @endif
                
                @if($transcript)
                    <button onclick="downloadTranscript()" 
                            class="w-full sm:w-auto inline-flex items-center justify-center px-4 py-2 bg-gray-600 text-white text-sm font-medium rounded-lg hover:bg-gray-700 transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <span class="truncate">{{ __('enhanced-calls.export') }}</span>
                    </button>
                @endif
                
                @if($urgencyLevel === 'dringend' || $appointmentRequested)
                    <button onclick="createFollowUp()" 
                            class="w-full sm:w-auto inline-flex items-center justify-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <span class="truncate">{{ __('enhanced-calls.schedule_followup') }}</span>
                    </button>
                @endif
            </div>
        </div>
    </div>

    {{-- TIER 2: CONTEXTUAL EXPANSION (5-30 seconds) --}}
    <div class="tier-2-contextual grid grid-cols-1 md:grid-cols-2 lg:grid-cols-12 gap-4 sm:gap-6 mb-6">
        {{-- Customer Intelligence Panel --}}
        <div class="col-span-1 md:col-span-1 lg:col-span-4 bg-white dark:bg-gray-800 rounded-xl shadow-lg p-4 sm:p-6">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center">
                <svg class="w-5 h-5 mr-2 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                {{ __('enhanced-calls.customer_intelligence') }}
            </h2>
            
            <div class="space-y-4">
                {{-- Identity --}}
                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-gray-500">{{ __('enhanced-calls.identity') }}</span>
                        @if($customerNumber)
                            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-800">
                                {{ __('enhanced-calls.verified_customer') }} #{{ $customerNumber }}
                            </span>
                        @endif
                    </div>
                    <p class="text-lg font-bold text-gray-900 dark:text-white truncate">{{ $callerName }}</p>
                    <p class="text-sm text-gray-600 dark:text-gray-300 truncate">{{ $companyName }}</p>
                    <p class="text-sm text-gray-500 mt-1 truncate">{{ GermanFormatter::formatPhoneNumber($call->from_number) ?? '-' }}</p>
                </div>

                {{-- Request Details --}}
                @if($customData['customer_request'] ?? false)
                    <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-4">
                        <p class="text-sm font-medium text-yellow-800 dark:text-yellow-200 mb-1">{{ __('enhanced-calls.customer_request') }}</p>
                        <p class="text-sm text-gray-700 dark:text-gray-300">{{ $customData['customer_request'] }}</p>
                    </div>
                @endif

                {{-- Call History --}}
                <div class="border-t pt-4">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-gray-500">{{ __('enhanced-calls.call_history') }}</span>
                        <span class="text-xs text-gray-400">{{ __('enhanced-calls.last_30_days') }}</span>
                    </div>
                    <div class="space-y-1">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">{{ __('enhanced-calls.total_calls') }}</span>
                            <span class="font-medium">{{ $call->customer ? $call->customer->calls()->count() : 1 }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">{{ __('enhanced-calls.no_shows') }}</span>
                            <span class="font-medium">{{ $call->no_show_count ?? 0 }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">{{ __('enhanced-calls.reschedules') }}</span>
                            <span class="font-medium">{{ $call->reschedule_count ?? 0 }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Performance Metrics Dashboard --}}
        <div class="col-span-1 md:col-span-1 lg:col-span-4 bg-white dark:bg-gray-800 rounded-xl shadow-lg p-4 sm:p-6">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center">
                <svg class="w-5 h-5 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                {{ __('enhanced-calls.performance_metrics') }}
            </h2>

            <div class="space-y-4">
                {{-- Latency Metrics --}}
                @if($latencyP50 > 0)
                    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-gray-700 dark:to-gray-600 rounded-lg p-4">
                        <p class="text-sm font-medium text-gray-700 dark:text-gray-200 mb-3">{{ __('enhanced-calls.response_latency') }} (ms)</p>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-xs">
                            <div class="bg-white dark:bg-gray-800 rounded p-2">
                                <span class="text-gray-500">P50</span>
                                <p class="font-bold text-blue-600">{{ GermanFormatter::formatNumber($latencyP50, 0) }} ms</p>
                            </div>
                            <div class="bg-white dark:bg-gray-800 rounded p-2">
                                <span class="text-gray-500">P90</span>
                                <p class="font-bold text-indigo-600">{{ GermanFormatter::formatNumber($latencyP90, 0) }} ms</p>
                            </div>
                            <div class="bg-white dark:bg-gray-800 rounded p-2">
                                <span class="text-gray-500">P95</span>
                                <p class="font-bold text-purple-600">{{ GermanFormatter::formatNumber($latencyP95, 0) }} ms</p>
                            </div>
                            <div class="bg-white dark:bg-gray-800 rounded p-2">
                                <span class="text-gray-500">P99</span>
                                <p class="font-bold text-red-600">{{ GermanFormatter::formatNumber($latencyP99, 0) }} ms</p>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Token Usage --}}
                @if($tokenUsage)
                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-600">{{ __('enhanced-calls.token_usage') }}</span>
                            <span class="text-lg font-bold text-gray-900 dark:text-white">{{ GermanFormatter::formatNumber($tokenUsage, 0) }}</span>
                        </div>
                        <div class="mt-2 w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-gradient-to-r from-green-400 to-blue-500 h-2 rounded-full" 
                                 style="width: {{ min(($tokenUsage / 4000) * 100, 100) }}%"></div>
                        </div>
                    </div>
                @endif

                {{-- Quality Score --}}
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">{{ __('enhanced-calls.audio_quality') }}</span>
                    <div class="flex items-center">
                        @for($i = 1; $i <= 5; $i++)
                            <svg class="w-4 h-4 {{ $i <= 4 ? 'text-yellow-400' : 'text-gray-300' }}" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                        @endfor
                    </div>
                </div>
            </div>
        </div>

        {{-- AI Analysis Summary --}}
        <div class="col-span-1 md:col-span-2 lg:col-span-4 bg-white dark:bg-gray-800 rounded-xl shadow-lg p-4 sm:p-6">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center">
                <svg class="w-5 h-5 mr-2 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                </svg>
                AI Analysis
            </h2>

            <div class="space-y-4">
                {{-- Summary --}}
                <div class="bg-gradient-to-r from-purple-50 to-pink-50 dark:from-gray-700 dark:to-gray-600 rounded-lg p-4">
                    <p class="text-sm font-medium text-purple-800 dark:text-purple-200 mb-2">Call Summary</p>
                    <p class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed">{{ $summary }}</p>
                </div>

                {{-- Language Detection --}}
                @if($detectedLanguage !== 'unknown')
                    <div class="flex items-center justify-between bg-gray-50 dark:bg-gray-700 rounded-lg p-3">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/>
                            </svg>
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-200">
                                {{ strtoupper($detectedLanguage) }}
                            </span>
                        </div>
                        <span class="text-sm text-gray-500">
                            {{ number_format($languageConfidence * 100, 0) }}% confidence
                        </span>
                    </div>
                @endif

                {{-- Intent Detection --}}
                @if($analysis['intent'] ?? false)
                    <div class="border-t pt-3">
                        <p class="text-sm font-medium text-gray-600 mb-2">Detected Intents</p>
                        <div class="flex flex-wrap gap-2">
                            @foreach((array)($analysis['intent'] ?? []) as $intent)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    {{ $intent }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- TIER 3: DETAILED ANALYSIS (30+ seconds, expandable) --}}
    <div class="tier-3-detailed space-y-6">
        {{-- Transcript Viewer --}}
        @if($transcript || !empty($transcriptObject))
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white flex items-center">
                        <svg class="w-5 h-5 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                        </svg>
                        Conversation Transcript
                    </h2>
                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
                        <input type="text" 
                               id="transcript-search"
                               placeholder="Search transcript..." 
                               class="w-full sm:w-auto sm:max-w-xs px-3 py-1 border border-gray-300 dark:border-gray-600 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <button onclick="toggleTranscriptView()" 
                                class="px-3 py-1 text-sm text-blue-600 hover:text-blue-800 border border-blue-600 rounded-lg sm:border-0">
                            Switch View
                        </button>
                    </div>
                </div>

                {{-- Structured Transcript Display --}}
                <div class="transcript-container max-h-64 sm:max-h-80 lg:max-h-96 overflow-y-auto overflow-x-hidden space-y-3" id="transcript-display">
                    @if(!empty($transcriptObject))
                        @foreach($transcriptObject as $message)
                            <div class="flex {{ $message['role'] === 'agent' ? 'justify-start' : 'justify-end' }}">
                                <div class="max-w-[85%] sm:max-w-[75%] lg:max-w-3xl {{ $message['role'] === 'agent' ? 'bg-blue-50 dark:bg-blue-900/20' : 'bg-gray-50 dark:bg-gray-700' }} rounded-lg p-3">
                                    <div class="flex items-center mb-1">
                                        <span class="text-xs font-medium {{ $message['role'] === 'agent' ? 'text-blue-600' : 'text-gray-600' }}">
                                            {{ $message['role'] === 'agent' ? 'Agent' : 'Customer' }}
                                        </span>
                                        @if(isset($message['timestamp']))
                                            <span class="text-xs text-gray-400 ml-2">{{ $message['timestamp'] }}</span>
                                        @endif
                                    </div>
                                    <p class="text-sm text-gray-800 dark:text-gray-200">{{ $message['content'] }}</p>
                                </div>
                            </div>
                        @endforeach
                    @elseif($transcript)
                        <div class="prose prose-sm dark:prose-invert max-w-none">
                            <pre class="whitespace-pre-wrap text-xs sm:text-sm overflow-x-auto">{{ $transcript }}</pre>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        {{-- Cost Breakdown Analysis --}}
        @if(!empty($costBreakdown))
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Cost Breakdown
                </h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($costBreakdown['product_costs'] ?? [] as $cost)
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-3">
                            <p class="text-xs text-gray-500 uppercase tracking-wider">{{ $cost['product'] ?? 'Service' }}</p>
                            <p class="text-lg font-bold text-gray-900 dark:text-white">
                                €{{ number_format($cost['unit_price'] ?? 0, 4) }}
                            </p>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    {{-- TIER 4: TECHNICAL/DEBUG (Admin only, on-demand) --}}
    @if($isAdmin)
        <details class="tier-4-technical mt-6 bg-gray-50 dark:bg-gray-800 rounded-xl">
            <summary class="cursor-pointer p-6 font-bold text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl">
                🔧 Technical Details & Debug Information
            </summary>
            <div class="p-6 border-t border-gray-200 dark:border-gray-700">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6">
                    {{-- System IDs --}}
                    <div>
                        <h3 class="font-bold text-sm text-gray-700 dark:text-gray-300 mb-3">System Identifiers</h3>
                        <dl class="space-y-2 text-xs">
                            <div class="flex">
                                <dt class="text-gray-500 w-40">RetellAI Call ID:</dt>
                                <dd class="text-gray-900 dark:text-white font-mono">{{ $retellCallId ?? 'N/A' }}</dd>
                            </div>
                            <div class="flex">
                                <dt class="text-gray-500 w-40">Agent ID:</dt>
                                <dd class="text-gray-900 dark:text-white font-mono">{{ $agentId ?? 'N/A' }}</dd>
                            </div>
                            <div class="flex">
                                <dt class="text-gray-500 w-40">Agent Version:</dt>
                                <dd class="text-gray-900 dark:text-white font-mono">v{{ $agentVersion }}</dd>
                            </div>
                            <div class="flex">
                                <dt class="text-gray-500 w-40">Cal.com Booking:</dt>
                                <dd class="text-gray-900 dark:text-white font-mono">{{ $calcomBookingId ?? 'None' }}</dd>
                            </div>
                        </dl>
                    </div>

                    {{-- Webhook Data --}}
                    <div>
                        <h3 class="font-bold text-sm text-gray-700 dark:text-gray-300 mb-3">Integration Status</h3>
                        <div class="space-y-2">
                            <div class="flex items-center">
                                <div class="w-2 h-2 bg-green-500 rounded-full mr-2"></div>
                                <span class="text-xs text-gray-600">RetellAI Connected</span>
                            </div>
                            @if($calcomBookingId)
                                <div class="flex items-center">
                                    <div class="w-2 h-2 bg-green-500 rounded-full mr-2"></div>
                                    <span class="text-xs text-gray-600">Cal.com Synced</span>
                                </div>
                            @endif
                            <div class="flex items-center">
                                <div class="w-2 h-2 bg-green-500 rounded-full mr-2"></div>
                                <span class="text-xs text-gray-600">Database Persisted</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Raw Data Viewers --}}
                @if(!empty($webhookData))
                    <div class="mt-6">
                        <h3 class="font-bold text-sm text-gray-700 dark:text-gray-300 mb-3">Raw Webhook Data</h3>
                        <pre class="bg-gray-900 text-green-400 p-2 sm:p-4 rounded-lg text-xs overflow-x-auto max-w-full">{{ json_encode($webhookData, JSON_PRETTY_PRINT) }}</pre>
                    </div>
                @endif

                {{-- Public Log Link --}}
                @if($publicLogUrl)
                    <div class="mt-4">
                        <a href="{{ $publicLogUrl }}" target="_blank" 
                           class="inline-flex items-center text-sm text-blue-600 hover:text-blue-800">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                            </svg>
                            View Public RetellAI Log
                        </a>
                    </div>
                @endif
            </div>
        </details>
    @endif

    {{-- JavaScript for Interactive Features --}}
    <script>
        // Audio Player
        function playAudio(url) {
            const audio = new Audio(url);
            audio.play();
        }

        // Transcript Search
        document.getElementById('transcript-search')?.addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const messages = document.querySelectorAll('#transcript-display > div');
            
            messages.forEach(message => {
                const text = message.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    message.style.display = 'flex';
                    // Highlight matching text
                    if (searchTerm) {
                        const regex = new RegExp(`(${searchTerm})`, 'gi');
                        message.innerHTML = message.innerHTML.replace(regex, '<mark>$1</mark>');
                    }
                } else {
                    message.style.display = 'none';
                }
            });
        });

        // Download Transcript
        function downloadTranscript() {
            const transcript = @json($transcript);
            const blob = new Blob([transcript], { type: 'text/plain' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `call-{{ $call->id }}-transcript.txt`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
        }

        // Create Follow-up
        function createFollowUp() {
            // Cal.com integration
            window.location.href = '/admin/appointments/create?call_id={{ $call->id }}';
        }

        // Toggle Transcript View
        function toggleTranscriptView() {
            const container = document.getElementById('transcript-display');
            container.classList.toggle('prose');
        }
    </script>
</x-filament-panels::page>