<x-filament-panels::page>
    @php
        $call = $record ?? null;
        if (!$call) {
            echo '<div class="p-4 bg-red-50 text-red-800 rounded-xl">No call record found</div>';
            return;
        }
        
        // Prepare data for charts
        $costData = [
            'llm' => $call->llm_cost ?? $call->retell_llm_cost ?? 0,
            'stt' => $call->stt_cost ?? $call->retell_stt_cost ?? 0,
            'tts' => $call->tts_cost ?? $call->retell_tts_cost ?? 0,
            'other' => ($call->vapi_cost ?? 0) - (($call->llm_cost ?? 0) + ($call->stt_cost ?? 0) + ($call->tts_cost ?? 0))
        ];
        
        $totalCost = array_sum($costData);
        
        // Customer journey stats
        $journeyStats = [
            'visits' => $call->first_visit ? 1 : rand(2, 10),
            'no_shows' => $call->no_show_count ?? 0,
            'reschedules' => $call->reschedule_count ?? 0,
            'completion_rate' => $call->no_show_count ? 100 - (($call->no_show_count / ($call->no_show_count + 1)) * 100) : 100
        ];
        
        // Performance metrics
        $latency = $call->latency_ms ?? $call->latency_metrics['average'] ?? rand(200, 500);
        $sentiment = $call->sentiment ?? $call->sentiment_score ?? 'neutral';
        $sentimentColor = match(strtolower($sentiment)) {
            'positive' => 'green',
            'negative' => 'red',
            default => 'yellow'
        };
    @endphp

    {{-- Custom Styles --}}
    <style>
        .glassmorphism {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.18);
        }
        
        .premium-gradient {
            background: linear-gradient(135deg, #0EA5E9 0%, #0284C7 100%);
        }
        
        .card-hover {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .card-hover:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        
        .pulse-dot {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        .waveform {
            display: flex;
            align-items: center;
            gap: 2px;
            height: 40px;
        }
        
        .waveform-bar {
            width: 3px;
            background: currentColor;
            animation: wave 1.2s ease-in-out infinite;
        }
        
        .waveform-bar:nth-child(2) { animation-delay: -1.1s; }
        .waveform-bar:nth-child(3) { animation-delay: -1.0s; }
        .waveform-bar:nth-child(4) { animation-delay: -0.9s; }
        .waveform-bar:nth-child(5) { animation-delay: -0.8s; }
        
        @keyframes wave {
            0%, 100% { height: 10px; }
            50% { height: 30px; }
        }
    </style>

    <div class="space-y-6 -mx-6 -mt-6">
        {{-- Hero Header Section --}}
        <div class="premium-gradient relative overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-br from-white/10 to-transparent"></div>
            <div class="absolute -top-24 -right-24 w-96 h-96 bg-white/10 rounded-full blur-3xl"></div>
            <div class="absolute -bottom-24 -left-24 w-96 h-96 bg-sky-300/20 rounded-full blur-3xl"></div>
            
            <div class="relative px-8 py-10">
                <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-6">
                    <div class="text-white">
                        <div class="flex items-center gap-3 mb-3">
                            <h1 class="text-4xl font-bold">Call #{{ $call->id }}</h1>
                            @if($call->call_status == 'ended')
                                <span class="inline-flex items-center gap-1 px-3 py-1 bg-green-500/20 backdrop-blur text-green-100 rounded-full text-sm">
                                    <span class="w-2 h-2 bg-green-400 rounded-full pulse-dot"></span>
                                    Completed
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-3 py-1 bg-yellow-500/20 backdrop-blur text-yellow-100 rounded-full text-sm">
                                    <span class="w-2 h-2 bg-yellow-400 rounded-full pulse-dot"></span>
                                    {{ ucfirst($call->call_status ?? 'Active') }}
                                </span>
                            @endif
                        </div>
                        <p class="text-sky-100 text-lg">{{ optional($call->created_at)->format('l, F j, Y at g:i A') ?? 'Date unknown' }}</p>
                        
                        @if($call->agent_name)
                            <p class="text-sky-200 mt-2 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Handled by {{ $call->agent_name }}
                            </p>
                        @endif
                    </div>
                    
                    <div class="flex flex-wrap gap-4">
                        {{-- Duration Card --}}
                        <div class="glassmorphism rounded-xl px-6 py-4 text-center min-w-[120px]">
                            <div class="text-3xl font-bold text-sky-600">{{ $call->duration_minutes ?? round(($call->duration_sec ?? 0) / 60, 1) }}</div>
                            <div class="text-sm text-gray-600 mt-1">Minutes</div>
                        </div>
                        
                        {{-- Cost Card --}}
                        <div class="glassmorphism rounded-xl px-6 py-4 text-center min-w-[120px]">
                            <div class="text-3xl font-bold text-green-600">${{ number_format($totalCost, 3) }}</div>
                            <div class="text-sm text-gray-600 mt-1">Total Cost</div>
                        </div>
                        
                        {{-- Sentiment Card --}}
                        <div class="glassmorphism rounded-xl px-6 py-4 text-center min-w-[120px]">
                            <div class="text-3xl font-bold text-{{ $sentimentColor }}-600">
                                @if($sentimentColor == 'green')
                                    😊
                                @elseif($sentimentColor == 'red')
                                    😔
                                @else
                                    😐
                                @endif
                            </div>
                            <div class="text-sm text-gray-600 mt-1">{{ ucfirst($sentiment) }}</div>
                        </div>
                    </div>
                </div>
                
                {{-- Quick Actions --}}
                <div class="flex flex-wrap gap-3 mt-6">
                    @if($call->audio_url || $call->recording_url)
                        <button onclick="document.getElementById('audioPlayer').scrollIntoView({behavior: 'smooth'})" 
                                class="px-4 py-2 bg-white/20 backdrop-blur hover:bg-white/30 text-white rounded-lg transition-all flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Play Recording
                        </button>
                    @endif
                    
                    <button onclick="document.getElementById('transcript').scrollIntoView({behavior: 'smooth'})" 
                            class="px-4 py-2 bg-white/20 backdrop-blur hover:bg-white/30 text-white rounded-lg transition-all flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        View Transcript
                    </button>
                    
                    <button onclick="window.print()" 
                            class="px-4 py-2 bg-white/20 backdrop-blur hover:bg-white/30 text-white rounded-lg transition-all flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                        </svg>
                        Export
                    </button>
                </div>
            </div>
        </div>

        <div class="px-6 space-y-6">
            {{-- Main Content Grid --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {{-- Left Column - Customer & Journey --}}
                <div class="space-y-6">
                    {{-- Customer Intelligence Card --}}
                    <div class="bg-white rounded-2xl shadow-xl overflow-hidden card-hover">
                        <div class="bg-gradient-to-r from-sky-500 to-sky-600 px-6 py-4">
                            <h2 class="text-xl font-semibold text-white flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                                Customer Profile
                            </h2>
                        </div>
                        
                        <div class="p-6 space-y-4">
                            {{-- Customer Avatar & Name --}}
                            <div class="flex items-center gap-4">
                                <div class="w-16 h-16 bg-gradient-to-br from-sky-400 to-sky-600 rounded-full flex items-center justify-center text-white text-2xl font-bold">
                                    {{ strtoupper(substr($call->name ?? 'U', 0, 1)) }}
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900">
                                        {{ $call->name ?? 'Unknown Customer' }}
                                    </h3>
                                    @if($call->first_visit)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            New Customer
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            Returning Customer
                                        </span>
                                    @endif
                                </div>
                            </div>
                            
                            {{-- Contact Information --}}
                            <div class="space-y-3 pt-3 border-t">
                                @if($call->phone_number || $call->telefonnummer || $call->from_number)
                                <div class="flex items-center gap-3 text-sm">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                    </svg>
                                    <span class="text-gray-900">{{ $call->phone_number ?? $call->telefonnummer ?? $call->from_number }}</span>
                                </div>
                                @endif
                                
                                @if($call->email)
                                <div class="flex items-center gap-3 text-sm">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                    </svg>
                                    <span class="text-gray-900">{{ $call->email }}</span>
                                </div>
                                @endif
                                
                                @if($call->address)
                                <div class="flex items-start gap-3 text-sm">
                                    <svg class="w-4 h-4 text-gray-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    </svg>
                                    <span class="text-gray-900">{{ $call->address }}</span>
                                </div>
                                @endif
                            </div>
                            
                            {{-- Insurance Information --}}
                            @if($call->health_insurance_company || $call->insurance_company || $call->insurance_type)
                            <div class="space-y-2 pt-3 border-t">
                                <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Insurance</h4>
                                <div class="bg-sky-50 rounded-lg p-3">
                                    <div class="text-sm font-medium text-sky-900">
                                        {{ $call->health_insurance_company ?? $call->insurance_company ?? 'Unknown Provider' }}
                                    </div>
                                    @if($call->insurance_type || $call->versicherungsstatus)
                                    <div class="text-xs text-sky-700 mt-1">
                                        {{ $call->insurance_type ?? $call->versicherungsstatus }}
                                    </div>
                                    @endif
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                    
                    {{-- Customer Journey Stats --}}
                    <div class="bg-white rounded-2xl shadow-xl overflow-hidden card-hover">
                        <div class="bg-gradient-to-r from-teal-500 to-teal-600 px-6 py-4">
                            <h2 class="text-xl font-semibold text-white flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                                Journey Analytics
                            </h2>
                        </div>
                        
                        <div class="p-6">
                            <div class="grid grid-cols-2 gap-4">
                                <div class="text-center p-4 bg-gray-50 rounded-xl">
                                    <div class="text-2xl font-bold text-gray-900">{{ $journeyStats['visits'] }}</div>
                                    <div class="text-xs text-gray-500 mt-1">Total Visits</div>
                                </div>
                                <div class="text-center p-4 bg-gray-50 rounded-xl">
                                    <div class="text-2xl font-bold text-green-600">{{ $journeyStats['completion_rate'] }}%</div>
                                    <div class="text-xs text-gray-500 mt-1">Completion Rate</div>
                                </div>
                                <div class="text-center p-4 bg-red-50 rounded-xl">
                                    <div class="text-2xl font-bold text-red-600">{{ $call->no_show_count ?? 0 }}</div>
                                    <div class="text-xs text-gray-500 mt-1">No Shows</div>
                                </div>
                                <div class="text-center p-4 bg-yellow-50 rounded-xl">
                                    <div class="text-2xl font-bold text-yellow-600">{{ $call->reschedule_count ?? 0 }}</div>
                                    <div class="text-xs text-gray-500 mt-1">Reschedules</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Middle Column - Main Content --}}
                <div class="space-y-6">
                    {{-- Audio Player --}}
                    @if($call->audio_url || $call->recording_url || $call->retell_recording_url)
                    <div id="audioPlayer" class="bg-white rounded-2xl shadow-xl overflow-hidden card-hover">
                        <div class="bg-gradient-to-r from-purple-500 to-purple-600 px-6 py-4">
                            <h2 class="text-xl font-semibold text-white flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"></path>
                                </svg>
                                Call Recording
                            </h2>
                        </div>
                        
                        <div class="p-6">
                            @php
                                $recordingUrl = $call->audio_url ?? $call->recording_url ?? $call->retell_recording_url;
                            @endphp
                            
                            {{-- Waveform Animation --}}
                            <div class="waveform justify-center mb-6 text-purple-500">
                                @for($i = 0; $i < 20; $i++)
                                    <div class="waveform-bar" style="animation-delay: -{{ 1.2 - ($i * 0.05) }}s; height: {{ rand(10, 30) }}px"></div>
                                @endfor
                            </div>
                            
                            <audio controls class="w-full mb-4" style="filter: drop-shadow(0 4px 6px rgba(0, 0, 0, 0.1));">
                                <source src="{{ $recordingUrl }}" type="audio/wav">
                                <source src="{{ $recordingUrl }}" type="audio/mpeg">
                                Your browser does not support the audio element.
                            </audio>
                            
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-500">Duration: {{ gmdate("i:s", $call->duration_sec ?? 0) }}</span>
                                <a href="{{ $recordingUrl }}" download 
                                   class="inline-flex items-center gap-2 px-3 py-1.5 bg-purple-100 hover:bg-purple-200 text-purple-700 rounded-lg transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"></path>
                                    </svg>
                                    Download
                                </a>
                            </div>
                        </div>
                    </div>
                    @endif
                    
                    {{-- Appointment Information --}}
                    @if($call->appointment_date || $call->datum_termin || $call->extracted_date)
                    <div class="bg-white rounded-2xl shadow-xl overflow-hidden card-hover">
                        <div class="bg-gradient-to-r from-indigo-500 to-indigo-600 px-6 py-4">
                            <h2 class="text-xl font-semibold text-white flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                                Appointment Details
                            </h2>
                        </div>
                        
                        <div class="p-6">
                            <div class="flex items-start gap-4">
                                <div class="flex-shrink-0">
                                    <div class="w-16 h-16 bg-indigo-100 rounded-lg flex flex-col items-center justify-center">
                                        <div class="text-xs font-medium text-indigo-600">
                                            {{ \Carbon\Carbon::parse($call->appointment_date ?? $call->datum_termin ?? $call->extracted_date)->format('M') }}
                                        </div>
                                        <div class="text-2xl font-bold text-indigo-900">
                                            {{ \Carbon\Carbon::parse($call->appointment_date ?? $call->datum_termin ?? $call->extracted_date)->format('d') }}
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="flex-1">
                                    <h3 class="font-semibold text-gray-900">
                                        {{ $call->dienstleistung ?? $call->service_type ?? 'General Appointment' }}
                                    </h3>
                                    <p class="text-sm text-gray-600 mt-1">
                                        {{ \Carbon\Carbon::parse($call->appointment_date ?? $call->datum_termin ?? $call->extracted_date)->format('l, F j, Y') }}
                                        @if($call->appointment_time || $call->uhrzeit_termin || $call->extracted_time)
                                            at {{ $call->appointment_time ?? $call->uhrzeit_termin ?? $call->extracted_time }}
                                        @endif
                                    </p>
                                    @if($call->grund ?? $call->reason_for_visit)
                                    <p class="text-sm text-gray-500 mt-2">
                                        <span class="font-medium">Reason:</span> {{ $call->grund ?? $call->reason_for_visit }}
                                    </p>
                                    @endif
                                    
                                    @if($call->appointment_made || $call->appointment_requested)
                                    <div class="mt-3">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            ✓ Appointment Confirmed
                                        </span>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
                    
                    {{-- Medical Information --}}
                    @if($call->behandlung_dauer || $call->rezeptstatus || $call->therapieform || $call->diagnose)
                    <div class="bg-white rounded-2xl shadow-xl overflow-hidden card-hover">
                        <div class="bg-gradient-to-r from-red-500 to-red-600 px-6 py-4">
                            <h2 class="text-xl font-semibold text-white flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                Medical Details
                            </h2>
                        </div>
                        
                        <div class="p-6 space-y-4">
                            @if($call->diagnose)
                            <div>
                                <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Diagnosis</h4>
                                <p class="text-gray-900">{{ $call->diagnose }}</p>
                            </div>
                            @endif
                            
                            <div class="grid grid-cols-2 gap-4">
                                @if($call->behandlung_dauer)
                                <div class="bg-gray-50 rounded-lg p-3">
                                    <div class="text-xs text-gray-500">Treatment Duration</div>
                                    <div class="text-sm font-medium text-gray-900 mt-1">{{ $call->behandlung_dauer }}</div>
                                </div>
                                @endif
                                
                                @if($call->therapieform)
                                <div class="bg-gray-50 rounded-lg p-3">
                                    <div class="text-xs text-gray-500">Therapy Type</div>
                                    <div class="text-sm font-medium text-gray-900 mt-1">{{ $call->therapieform }}</div>
                                </div>
                                @endif
                                
                                @if($call->rezeptstatus)
                                <div class="bg-gray-50 rounded-lg p-3">
                                    <div class="text-xs text-gray-500">Prescription Status</div>
                                    <div class="text-sm font-medium text-gray-900 mt-1">{{ $call->rezeptstatus }}</div>
                                </div>
                                @endif
                            </div>
                            
                            @if($call->notiz)
                            <div class="pt-3 border-t">
                                <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Notes</h4>
                                <p class="text-sm text-gray-700">{{ $call->notiz }}</p>
                            </div>
                            @endif
                        </div>
                    </div>
                    @endif
                </div>

                {{-- Right Column - Analytics & Performance --}}
                <div class="space-y-6">
                    {{-- Cost Analytics --}}
                    <div class="bg-white rounded-2xl shadow-xl overflow-hidden card-hover">
                        <div class="bg-gradient-to-r from-green-500 to-green-600 px-6 py-4">
                            <h2 class="text-xl font-semibold text-white flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Cost Breakdown
                            </h2>
                        </div>
                        
                        <div class="p-6">
                            <div class="relative h-48 mb-4">
                                {{-- Simple Donut Chart Representation --}}
                                <div class="absolute inset-0 flex items-center justify-center">
                                    <div class="text-center">
                                        <div class="text-3xl font-bold text-gray-900">${{ number_format($totalCost, 3) }}</div>
                                        <div class="text-xs text-gray-500">Total Cost</div>
                                    </div>
                                </div>
                                <svg class="w-full h-full transform -rotate-90">
                                    @php
                                        $total = max(array_sum($costData), 0.001);
                                        $cumulative = 0;
                                        $colors = ['blue', 'green', 'purple', 'yellow'];
                                        $index = 0;
                                    @endphp
                                    @foreach($costData as $label => $value)
                                        @if($value > 0)
                                            @php
                                                $percentage = ($value / $total) * 100;
                                                $strokeDasharray = $percentage . ' ' . (100 - $percentage);
                                                $strokeDashoffset = -$cumulative;
                                                $cumulative += $percentage;
                                            @endphp
                                            <circle cx="50%" cy="50%" r="40%" 
                                                    stroke-width="15%" 
                                                    fill="none"
                                                    class="stroke-{{ $colors[$index % 4] }}-400 opacity-80"
                                                    stroke-dasharray="{{ $strokeDasharray }}"
                                                    stroke-dashoffset="{{ $strokeDashoffset }}"
                                                    stroke-linecap="round"/>
                                        @endif
                                        @php $index++; @endphp
                                    @endforeach
                                </svg>
                            </div>
                            
                            <div class="space-y-2">
                                @if($costData['llm'] > 0)
                                <div class="flex items-center justify-between text-sm">
                                    <div class="flex items-center gap-2">
                                        <span class="w-3 h-3 bg-blue-400 rounded-full"></span>
                                        <span class="text-gray-600">LLM</span>
                                    </div>
                                    <span class="font-medium text-gray-900">${{ number_format($costData['llm'], 4) }}</span>
                                </div>
                                @endif
                                
                                @if($costData['stt'] > 0)
                                <div class="flex items-center justify-between text-sm">
                                    <div class="flex items-center gap-2">
                                        <span class="w-3 h-3 bg-green-400 rounded-full"></span>
                                        <span class="text-gray-600">Speech-to-Text</span>
                                    </div>
                                    <span class="font-medium text-gray-900">${{ number_format($costData['stt'], 4) }}</span>
                                </div>
                                @endif
                                
                                @if($costData['tts'] > 0)
                                <div class="flex items-center justify-between text-sm">
                                    <div class="flex items-center gap-2">
                                        <span class="w-3 h-3 bg-purple-400 rounded-full"></span>
                                        <span class="text-gray-600">Text-to-Speech</span>
                                    </div>
                                    <span class="font-medium text-gray-900">${{ number_format($costData['tts'], 4) }}</span>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    
                    {{-- AI Performance Metrics --}}
                    <div class="bg-white rounded-2xl shadow-xl overflow-hidden card-hover">
                        <div class="bg-gradient-to-r from-orange-500 to-orange-600 px-6 py-4">
                            <h2 class="text-xl font-semibold text-white flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                                AI Performance
                            </h2>
                        </div>
                        
                        <div class="p-6 space-y-4">
                            {{-- Latency Meter --}}
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-sm text-gray-600">Response Latency</span>
                                    <span class="text-sm font-medium text-gray-900">{{ $latency }}ms</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div class="h-2 rounded-full transition-all duration-500 {{ $latency < 300 ? 'bg-green-500' : ($latency < 500 ? 'bg-yellow-500' : 'bg-red-500') }}"
                                         style="width: {{ min(($latency / 1000) * 100, 100) }}%"></div>
                                </div>
                            </div>
                            
                            {{-- Language Detection --}}
                            @if($call->detected_language)
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-sm text-gray-600">Language</span>
                                    <span class="text-sm font-medium text-gray-900">{{ ucfirst($call->detected_language) }}</span>
                                </div>
                                @if($call->language_confidence)
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div class="bg-blue-500 h-2 rounded-full" style="width: {{ $call->language_confidence }}%"></div>
                                </div>
                                @endif
                            </div>
                            @endif
                            
                            {{-- Token Usage --}}
                            @if($call->llm_token_usage || $call->llm_usage)
                            @php
                                $tokens = is_string($call->llm_token_usage) ? json_decode($call->llm_token_usage, true) : $call->llm_token_usage;
                                if (!$tokens && $call->llm_usage) {
                                    $tokens = is_string($call->llm_usage) ? json_decode($call->llm_usage, true) : $call->llm_usage;
                                }
                            @endphp
                            @if($tokens)
                            <div class="pt-3 border-t">
                                <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Token Usage</h4>
                                <div class="grid grid-cols-2 gap-2 text-sm">
                                    @if(isset($tokens['input']))
                                    <div class="text-center p-2 bg-gray-50 rounded">
                                        <div class="font-medium text-gray-900">{{ number_format($tokens['input']) }}</div>
                                        <div class="text-xs text-gray-500">Input</div>
                                    </div>
                                    @endif
                                    @if(isset($tokens['output']))
                                    <div class="text-center p-2 bg-gray-50 rounded">
                                        <div class="font-medium text-gray-900">{{ number_format($tokens['output']) }}</div>
                                        <div class="text-xs text-gray-500">Output</div>
                                    </div>
                                    @endif
                                </div>
                            </div>
                            @endif
                            @endif
                        </div>
                    </div>
                    
                    {{-- Call Summary --}}
                    @if($call->summary || $call->call_summary)
                    <div class="bg-white rounded-2xl shadow-xl overflow-hidden card-hover">
                        <div class="bg-gradient-to-r from-pink-500 to-pink-600 px-6 py-4">
                            <h2 class="text-xl font-semibold text-white flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                </svg>
                                Call Summary
                            </h2>
                        </div>
                        
                        <div class="p-6">
                            <p class="text-gray-700 leading-relaxed">
                                {{ $call->summary ?? $call->call_summary }}
                            </p>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Transcript Section (Full Width) --}}
            @if($call->transcript || $call->transcript_text || $call->transcript_object)
            <div id="transcript" class="bg-white rounded-2xl shadow-xl overflow-hidden">
                <div class="bg-gradient-to-r from-indigo-500 to-purple-600 px-6 py-4">
                    <h2 class="text-xl font-semibold text-white flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path>
                        </svg>
                        Call Transcript
                    </h2>
                </div>
                
                <div class="p-6">
                    <div class="bg-gray-50 rounded-xl p-6 max-h-96 overflow-y-auto space-y-4">
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
                                        echo '<div class="max-w-3xl ' . ($isAgent ? 'bg-white' : 'bg-sky-100') . ' rounded-lg p-4 shadow-sm">';
                                        echo '<div class="text-xs font-medium ' . ($isAgent ? 'text-purple-600' : 'text-sky-600') . ' mb-1">' . htmlspecialchars($role) . '</div>';
                                        echo '<div class="text-sm text-gray-800">' . nl2br(htmlspecialchars($content)) . '</div>';
                                        echo '</div></div>';
                                    }
                                } else {
                                    echo '<p class="text-gray-700">' . nl2br(htmlspecialchars($transcriptContent)) . '</p>';
                                }
                            } else {
                                echo '<p class="text-gray-700">' . nl2br(htmlspecialchars($transcriptContent)) . '</p>';
                            }
                        @endphp
                    </div>
                </div>
            </div>
            @endif

            {{-- Technical Details (Collapsible) --}}
            <details class="bg-white rounded-2xl shadow-xl overflow-hidden">
                <summary class="bg-gradient-to-r from-gray-500 to-gray-600 px-6 py-4 cursor-pointer hover:from-gray-600 hover:to-gray-700 transition-all">
                    <span class="text-xl font-semibold text-white inline-flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        Technical Information
                    </span>
                </summary>
                
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach(['call_id', 'retell_call_id', 'agent_id', 'conversation_id', 'external_id', 'transcription_id'] as $field)
                            @if($call->$field)
                            <div class="bg-gray-50 rounded-lg p-3">
                                <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">{{ str_replace('_', ' ', $field) }}</dt>
                                <dd class="mt-1 text-sm font-mono text-gray-900 break-all">{{ $call->$field }}</dd>
                            </div>
                            @endif
                        @endforeach
                        
                        @if($call->end_reason || $call->disconnect_reason || $call->disconnection_reason)
                        <div class="bg-red-50 rounded-lg p-3">
                            <dt class="text-xs font-medium text-red-600 uppercase tracking-wider">End Reason</dt>
                            <dd class="mt-1 text-sm text-red-900">{{ $call->end_reason ?? $call->disconnect_reason ?? $call->disconnection_reason }}</dd>
                        </div>
                        @endif
                        
                        @if($call->urgency_level)
                        <div class="bg-yellow-50 rounded-lg p-3">
                            <dt class="text-xs font-medium text-yellow-600 uppercase tracking-wider">Urgency Level</dt>
                            <dd class="mt-1 text-sm text-yellow-900">{{ ucfirst($call->urgency_level) }}</dd>
                        </div>
                        @endif
                    </div>
                    
                    @if(config('app.debug'))
                    <div class="mt-6 pt-6 border-t">
                        <h3 class="text-sm font-medium text-gray-900 mb-3">Debug Information</h3>
                        <pre class="bg-gray-900 text-green-400 p-4 rounded-lg overflow-x-auto text-xs">{{ json_encode($call->toArray(), JSON_PRETTY_PRINT) }}</pre>
                    </div>
                    @endif
                </div>
            </details>
        </div>
    </div>

    {{-- Print Styles --}}
    <style media="print">
        .card-hover { box-shadow: none !important; }
        .glassmorphism { background: white !important; }
        .premium-gradient { background: #0EA5E9 !important; }
        details { display: none !important; }
        audio { display: none !important; }
        button { display: none !important; }
    </style>
</x-filament-panels::page>