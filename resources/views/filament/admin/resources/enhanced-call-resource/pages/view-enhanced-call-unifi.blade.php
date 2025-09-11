<x-filament-panels::page>
    @php
        $call = $record ?? null;
        if (!$call) {
            echo '<div class="p-4 bg-red-50 text-red-800 rounded-lg">No call record found</div>';
            return;
        }
        
        // Prepare clean data
        $duration = $call->duration_minutes ?? round(($call->duration_sec ?? 0) / 60, 1);
        $totalCost = ($call->total_cost ?? 0) + ($call->llm_cost ?? 0) + ($call->stt_cost ?? 0) + ($call->tts_cost ?? 0);
        $status = $call->call_status ?? 'unknown';
        $sentiment = strtolower($call->sentiment ?? 'neutral');
        
        // Clean status badge colors
        $statusColor = match($status) {
            'ended', 'completed' => 'green',
            'active', 'in_progress' => 'blue',
            'failed', 'error' => 'red',
            default => 'gray'
        };
        
        $sentimentIcon = match($sentiment) {
            'positive' => '👍',
            'negative' => '👎',
            default => '➖'
        };
    @endphp

    {{-- Clean, minimal styles --}}
    <style>
        /* UniFi-inspired clean design */
        .unifi-card {
            background: white;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            transition: box-shadow 0.2s;
        }
        
        .unifi-card:hover {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        
        .stat-value {
            font-variant-numeric: tabular-nums;
        }
        
        /* Clean scrollbar */
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        
        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f3f4f6;
            border-radius: 3px;
        }
        
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #d1d5db;
            border-radius: 3px;
        }
        
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #9ca3af;
        }
    </style>

    <div class="min-h-screen bg-gray-50">
        {{-- Clean Header --}}
        <header class="bg-white border-b border-gray-200 mb-6 -mx-6 -mt-6">
            <div class="px-6 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <h1 class="text-2xl font-semibold text-gray-900">Call #{{ $call->id }}</h1>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $statusColor }}-100 text-{{ $statusColor }}-800">
                            {{ ucfirst(str_replace('_', ' ', $status)) }}
                        </span>
                    </div>
                    <div class="flex items-center space-x-2 text-sm text-gray-500">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span>{{ optional($call->created_at)->format('M j, Y g:i A') }}</span>
                    </div>
                </div>
            </div>
        </header>

        {{-- Main Content Container --}}
        <div class="max-w-7xl mx-auto">
            {{-- Key Metrics Row --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                {{-- Duration Card --}}
                <div class="unifi-card p-4">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="p-2 bg-blue-50 rounded-lg">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-500">Duration</p>
                            <p class="text-xl font-semibold text-gray-900 stat-value">{{ $duration }} min</p>
                        </div>
                    </div>
                </div>

                {{-- Cost Card --}}
                <div class="unifi-card p-4">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="p-2 bg-green-50 rounded-lg">
                                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-500">Total Cost</p>
                            <p class="text-xl font-semibold text-gray-900 stat-value">${{ number_format($totalCost, 3) }}</p>
                        </div>
                    </div>
                </div>

                {{-- Sentiment Card --}}
                <div class="unifi-card p-4">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="p-2 bg-purple-50 rounded-lg">
                                <span class="text-xl">{{ $sentimentIcon }}</span>
                            </div>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-500">Sentiment</p>
                            <p class="text-xl font-semibold text-gray-900">{{ ucfirst($sentiment) }}</p>
                        </div>
                    </div>
                </div>

                {{-- Latency Card --}}
                @if($call->latency_ms)
                <div class="unifi-card p-4">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="p-2 bg-orange-50 rounded-lg">
                                <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-500">Latency</p>
                            <p class="text-xl font-semibold text-gray-900 stat-value">{{ $call->latency_ms }}ms</p>
                        </div>
                    </div>
                </div>
                @endif
            </div>

            {{-- Main Content Grid --}}
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                {{-- Left Sidebar - Customer Info --}}
                <aside class="lg:col-span-3 space-y-4">
                    {{-- Customer Card --}}
                    <div class="unifi-card p-5">
                        <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider mb-4">Customer</h3>
                        
                        <div class="space-y-3">
                            {{-- Avatar and Name --}}
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center">
                                    <span class="text-gray-600 font-medium">
                                        {{ strtoupper(substr($call->name ?? 'U', 0, 1)) }}
                                    </span>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-gray-900">
                                        {{ $call->name ?? 'Unknown' }}
                                    </p>
                                    @if($call->first_visit)
                                        <span class="text-xs text-green-600">New Customer</span>
                                    @else
                                        <span class="text-xs text-gray-500">Returning</span>
                                    @endif
                                </div>
                            </div>

                            {{-- Contact Details --}}
                            @if($call->phone_number || $call->telefonnummer)
                            <div class="flex items-center text-sm">
                                <svg class="w-4 h-4 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                </svg>
                                <span class="text-gray-700">{{ $call->phone_number ?? $call->telefonnummer }}</span>
                            </div>
                            @endif

                            @if($call->email)
                            <div class="flex items-center text-sm">
                                <svg class="w-4 h-4 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                </svg>
                                <span class="text-gray-700 truncate">{{ $call->email }}</span>
                            </div>
                            @endif
                        </div>
                    </div>

                    {{-- Journey Stats --}}
                    <div class="unifi-card p-5">
                        <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider mb-4">Journey</h3>
                        
                        <div class="space-y-3">
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">No Shows</span>
                                <span class="text-sm font-medium text-gray-900">{{ $call->no_show_count ?? 0 }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">Reschedules</span>
                                <span class="text-sm font-medium text-gray-900">{{ $call->reschedule_count ?? 0 }}</span>
                            </div>
                            @if($call->first_visit !== null)
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">First Visit</span>
                                <span class="text-sm font-medium {{ $call->first_visit ? 'text-green-600' : 'text-gray-900' }}">
                                    {{ $call->first_visit ? 'Yes' : 'No' }}
                                </span>
                            </div>
                            @endif
                        </div>
                    </div>

                    {{-- Insurance Info --}}
                    @if($call->health_insurance_company || $call->insurance_company || $call->insurance_type)
                    <div class="unifi-card p-5">
                        <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider mb-4">Insurance</h3>
                        
                        <div class="space-y-2">
                            @if($call->health_insurance_company || $call->insurance_company)
                            <p class="text-sm text-gray-700">
                                {{ $call->health_insurance_company ?? $call->insurance_company }}
                            </p>
                            @endif
                            @if($call->insurance_type)
                            <p class="text-xs text-gray-500">{{ $call->insurance_type }}</p>
                            @endif
                        </div>
                    </div>
                    @endif
                </aside>

                {{-- Main Content Area --}}
                <section class="lg:col-span-6 space-y-4">
                    {{-- Audio Player --}}
                    @if($call->audio_url || $call->recording_url || $call->retell_recording_url)
                    <div class="unifi-card p-5">
                        <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider mb-4">Recording</h3>
                        
                        @php
                            $audioUrl = $call->audio_url ?? $call->recording_url ?? $call->retell_recording_url;
                        @endphp
                        
                        <audio controls class="w-full">
                            <source src="{{ $audioUrl }}" type="audio/wav">
                            <source src="{{ $audioUrl }}" type="audio/mpeg">
                            Your browser does not support the audio element.
                        </audio>
                        
                        <div class="mt-3 flex items-center justify-between text-sm">
                            <span class="text-gray-500">Duration: {{ gmdate("i:s", $call->duration_sec ?? 0) }}</span>
                            <a href="{{ $audioUrl }}" download class="text-blue-600 hover:text-blue-800 flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"></path>
                                </svg>
                                Download
                            </a>
                        </div>
                    </div>
                    @endif

                    {{-- Appointment Details --}}
                    @if($call->appointment_date || $call->datum_termin || $call->extracted_date)
                    <div class="unifi-card p-5">
                        <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider mb-4">Appointment</h3>
                        
                        <div class="flex items-start space-x-4">
                            <div class="flex-shrink-0">
                                <div class="w-12 h-12 bg-blue-50 rounded-lg flex items-center justify-center">
                                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900">
                                    {{ $call->dienstleistung ?? $call->service_type ?? 'Appointment' }}
                                </p>
                                <p class="text-sm text-gray-600 mt-1">
                                    {{ \Carbon\Carbon::parse($call->appointment_date ?? $call->datum_termin ?? $call->extracted_date)->format('l, F j, Y') }}
                                    @if($call->appointment_time ?? $call->uhrzeit_termin ?? $call->extracted_time)
                                        at {{ $call->appointment_time ?? $call->uhrzeit_termin ?? $call->extracted_time }}
                                    @endif
                                </p>
                                @if($call->grund ?? $call->reason_for_visit)
                                <p class="text-sm text-gray-500 mt-2">
                                    {{ $call->grund ?? $call->reason_for_visit }}
                                </p>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endif

                    {{-- Summary --}}
                    @if($call->summary || $call->call_summary)
                    <div class="unifi-card p-5">
                        <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider mb-4">Summary</h3>
                        <p class="text-sm text-gray-700 leading-relaxed">
                            {{ $call->summary ?? $call->call_summary }}
                        </p>
                    </div>
                    @endif

                    {{-- Transcript --}}
                    @if($call->transcript || $call->transcript_text || $call->transcript_object)
                    <div class="unifi-card p-5">
                        <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider mb-4">Transcript</h3>
                        
                        <div class="max-h-96 overflow-y-auto custom-scrollbar space-y-3">
                            @php
                                $transcriptContent = $call->transcript ?? $call->transcript_text ?? '';
                                if (empty($transcriptContent) && $call->transcript_object) {
                                    $transcriptObj = is_string($call->transcript_object) ? json_decode($call->transcript_object, true) : $call->transcript_object;
                                    if (is_array($transcriptObj)) {
                                        foreach($transcriptObj as $item) {
                                            $role = $item['role'] ?? 'Unknown';
                                            $content = $item['content'] ?? '';
                                            $isAgent = str_contains(strtolower($role), 'agent') || str_contains(strtolower($role), 'assistant');
                                            
                                            echo '<div class="flex ' . ($isAgent ? 'justify-start' : 'justify-end') . '">';
                                            echo '<div class="max-w-xs lg:max-w-md px-4 py-2 rounded-lg ' . 
                                                 ($isAgent ? 'bg-gray-100 text-gray-800' : 'bg-blue-100 text-blue-800') . '">';
                                            echo '<p class="text-xs font-medium mb-1">' . htmlspecialchars($role) . '</p>';
                                            echo '<p class="text-sm">' . nl2br(htmlspecialchars($content)) . '</p>';
                                            echo '</div></div>';
                                        }
                                    } else {
                                        echo '<p class="text-sm text-gray-700">' . nl2br(htmlspecialchars($transcriptContent)) . '</p>';
                                    }
                                } else {
                                    echo '<p class="text-sm text-gray-700">' . nl2br(htmlspecialchars($transcriptContent)) . '</p>';
                                }
                            @endphp
                        </div>
                    </div>
                    @endif
                </section>

                {{-- Right Sidebar - Metrics --}}
                <aside class="lg:col-span-3 space-y-4">
                    {{-- Cost Breakdown --}}
                    @if($call->llm_cost || $call->stt_cost || $call->tts_cost || $call->total_cost)
                    <div class="unifi-card p-5">
                        <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider mb-4">Cost Analysis</h3>
                        
                        <div class="space-y-3">
                            @if($call->llm_cost || $call->retell_llm_cost)
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">LLM</span>
                                <span class="text-sm font-medium text-gray-900 stat-value">
                                    ${{ number_format($call->llm_cost ?? $call->retell_llm_cost ?? 0, 4) }}
                                </span>
                            </div>
                            @endif
                            
                            @if($call->stt_cost || $call->retell_stt_cost)
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">Speech-to-Text</span>
                                <span class="text-sm font-medium text-gray-900 stat-value">
                                    ${{ number_format($call->stt_cost ?? $call->retell_stt_cost ?? 0, 4) }}
                                </span>
                            </div>
                            @endif
                            
                            @if($call->tts_cost || $call->retell_tts_cost)
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">Text-to-Speech</span>
                                <span class="text-sm font-medium text-gray-900 stat-value">
                                    ${{ number_format($call->tts_cost ?? $call->retell_tts_cost ?? 0, 4) }}
                                </span>
                            </div>
                            @endif
                            
                            <div class="pt-3 border-t border-gray-200">
                                <div class="flex justify-between items-center">
                                    <span class="text-sm font-medium text-gray-900">Total</span>
                                    <span class="text-sm font-semibold text-gray-900 stat-value">
                                        ${{ number_format($totalCost, 3) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    {{-- AI Performance --}}
                    @if($call->latency_ms || $call->detected_language || $call->llm_token_usage)
                    <div class="unifi-card p-5">
                        <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider mb-4">AI Metrics</h3>
                        
                        <div class="space-y-3">
                            @if($call->latency_ms)
                            <div>
                                <div class="flex justify-between items-center mb-1">
                                    <span class="text-sm text-gray-600">Response Latency</span>
                                    <span class="text-sm font-medium text-gray-900">{{ $call->latency_ms }}ms</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-1.5">
                                    @php
                                        $latencyPercent = min(($call->latency_ms / 1000) * 100, 100);
                                        $latencyColor = $call->latency_ms < 300 ? 'bg-green-500' : ($call->latency_ms < 500 ? 'bg-yellow-500' : 'bg-red-500');
                                    @endphp
                                    <div class="{{ $latencyColor }} h-1.5 rounded-full" style="width: {{ $latencyPercent }}%"></div>
                                </div>
                            </div>
                            @endif
                            
                            @if($call->detected_language)
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">Language</span>
                                <span class="text-sm font-medium text-gray-900">{{ ucfirst($call->detected_language) }}</span>
                            </div>
                            @endif
                            
                            @if($call->language_confidence)
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">Confidence</span>
                                <span class="text-sm font-medium text-gray-900">{{ $call->language_confidence }}%</span>
                            </div>
                            @endif
                        </div>
                    </div>
                    @endif

                    {{-- Medical Details --}}
                    @if($call->behandlung_dauer || $call->rezeptstatus || $call->diagnose)
                    <div class="unifi-card p-5">
                        <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider mb-4">Medical</h3>
                        
                        <div class="space-y-3">
                            @if($call->diagnose)
                            <div>
                                <p class="text-xs text-gray-500 uppercase tracking-wider">Diagnosis</p>
                                <p class="text-sm text-gray-900 mt-1">{{ $call->diagnose }}</p>
                            </div>
                            @endif
                            
                            @if($call->behandlung_dauer)
                            <div>
                                <p class="text-xs text-gray-500 uppercase tracking-wider">Treatment Duration</p>
                                <p class="text-sm text-gray-900 mt-1">{{ $call->behandlung_dauer }}</p>
                            </div>
                            @endif
                            
                            @if($call->rezeptstatus)
                            <div>
                                <p class="text-xs text-gray-500 uppercase tracking-wider">Prescription</p>
                                <p class="text-sm text-gray-900 mt-1">{{ $call->rezeptstatus }}</p>
                            </div>
                            @endif
                        </div>
                    </div>
                    @endif

                    {{-- Technical Info --}}
                    <details class="unifi-card">
                        <summary class="px-5 py-4 cursor-pointer hover:bg-gray-50 rounded-t-lg">
                            <span class="text-sm font-semibold text-gray-900 uppercase tracking-wider">Technical</span>
                        </summary>
                        <div class="px-5 pb-5 space-y-2 text-xs">
                            @if($call->call_id)
                            <div class="flex justify-between">
                                <span class="text-gray-500">Call ID</span>
                                <span class="font-mono text-gray-700">{{ substr($call->call_id, 0, 12) }}...</span>
                            </div>
                            @endif
                            @if($call->agent_id)
                            <div class="flex justify-between">
                                <span class="text-gray-500">Agent ID</span>
                                <span class="font-mono text-gray-700">{{ substr($call->agent_id, 0, 12) }}...</span>
                            </div>
                            @endif
                            @if($call->end_reason)
                            <div class="flex justify-between">
                                <span class="text-gray-500">End Reason</span>
                                <span class="text-gray-700">{{ $call->end_reason }}</span>
                            </div>
                            @endif
                        </div>
                    </details>
                </aside>
            </div>
        </div>
    </div>
</x-filament-panels::page>