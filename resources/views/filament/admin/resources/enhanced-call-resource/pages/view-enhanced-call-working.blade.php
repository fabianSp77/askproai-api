<x-filament-panels::page>
    @php
        $call = $record ?? null;
        if (!$call) {
            echo '<div class="p-4 bg-red-50 text-red-800 rounded">No call record found</div>';
            return;
        }
    @endphp

    <div class="space-y-6">
        {{-- Header Section with Call Summary --}}
        <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg shadow-lg p-6 text-white">
            <div class="flex justify-between items-start">
                <div>
                    <h1 class="text-3xl font-bold mb-2">Call #{{ $call->id }}</h1>
                    <p class="text-blue-100">{{ optional($call->created_at)->format('F j, Y at g:i A') ?? 'Date unknown' }}</p>
                </div>
                <div class="text-right">
                    <div class="text-2xl font-bold">{{ $call->duration_minutes ?? 0 }} min</div>
                    <div class="text-blue-100">Duration</div>
                </div>
            </div>
        </div>

        {{-- Call Recording Section --}}
        @if($call->audio_url || $call->recording_url || $call->retell_recording_url)
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold mb-4 flex items-center">
                <svg class="w-5 h-5 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"></path>
                </svg>
                Call Recording
            </h2>
            
            @php
                $recordingUrl = $call->audio_url ?? $call->recording_url ?? $call->retell_recording_url;
            @endphp
            
            @if($recordingUrl)
                <audio controls class="w-full mb-4">
                    <source src="{{ $recordingUrl }}" type="audio/wav">
                    <source src="{{ $recordingUrl }}" type="audio/mpeg">
                    Your browser does not support the audio element.
                </audio>
                
                <div class="flex gap-4 text-sm text-gray-600">
                    <a href="{{ $recordingUrl }}" download class="flex items-center hover:text-blue-600">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"></path>
                        </svg>
                        Download Recording
                    </a>
                </div>
            @else
                <p class="text-gray-500">No recording available</p>
            @endif
        </div>
        @endif

        {{-- Customer Information Section --}}
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold mb-4 flex items-center">
                <svg class="w-5 h-5 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
                Customer Information
            </h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @if($call->customer_name)
                <div>
                    <dt class="text-sm font-medium text-gray-500">Name</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $call->customer_name }}</dd>
                </div>
                @endif
                
                @if($call->customer_phone || $call->from_number)
                <div>
                    <dt class="text-sm font-medium text-gray-500">Phone</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $call->customer_phone ?? $call->from_number }}</dd>
                </div>
                @endif
                
                @if($call->customer_email)
                <div>
                    <dt class="text-sm font-medium text-gray-500">Email</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $call->customer_email }}</dd>
                </div>
                @endif
                
                @if($call->birthdate)
                <div>
                    <dt class="text-sm font-medium text-gray-500">Birth Date</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $call->birthdate }}</dd>
                </div>
                @endif
                
                @if($call->address)
                <div>
                    <dt class="text-sm font-medium text-gray-500">Address</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $call->address }}</dd>
                </div>
                @endif
                
                @if($call->postcode)
                <div>
                    <dt class="text-sm font-medium text-gray-500">Postcode</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $call->postcode }}</dd>
                </div>
                @endif
            </div>
        </div>

        {{-- Healthcare Information (June Feature) --}}
        @if($call->health_insurance_company || $call->insurance_type || $call->behandlung_dauer || $call->rezeptstatus)
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold mb-4 flex items-center">
                <svg class="w-5 h-5 mr-2 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                Healthcare Details
            </h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @if($call->health_insurance_company)
                <div>
                    <dt class="text-sm font-medium text-gray-500">Insurance Company</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $call->health_insurance_company }}</dd>
                </div>
                @endif
                
                @if($call->insurance_type)
                <div>
                    <dt class="text-sm font-medium text-gray-500">Insurance Type</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $call->insurance_type }}</dd>
                </div>
                @endif
                
                @if($call->behandlung_dauer)
                <div>
                    <dt class="text-sm font-medium text-gray-500">Treatment Duration</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $call->behandlung_dauer }}</dd>
                </div>
                @endif
                
                @if($call->rezeptstatus)
                <div>
                    <dt class="text-sm font-medium text-gray-500">Prescription Status</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $call->rezeptstatus }}</dd>
                </div>
                @endif
                
                @if($call->therapieform)
                <div>
                    <dt class="text-sm font-medium text-gray-500">Therapy Type</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $call->therapieform }}</dd>
                </div>
                @endif
                
                @if($call->diagnose)
                <div>
                    <dt class="text-sm font-medium text-gray-500">Diagnosis</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $call->diagnose }}</dd>
                </div>
                @endif
            </div>
        </div>
        @endif

        {{-- Appointment Information (June Feature) --}}
        @if($call->appointment_date || $call->appointment_time || $call->extracted_appointment_date)
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold mb-4 flex items-center">
                <svg class="w-5 h-5 mr-2 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
                Appointment Details
            </h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @if($call->appointment_date || $call->extracted_appointment_date)
                <div>
                    <dt class="text-sm font-medium text-gray-500">Date</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $call->appointment_date ?? $call->extracted_appointment_date }}</dd>
                </div>
                @endif
                
                @if($call->appointment_time || $call->extracted_appointment_time)
                <div>
                    <dt class="text-sm font-medium text-gray-500">Time</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $call->appointment_time ?? $call->extracted_appointment_time }}</dd>
                </div>
                @endif
                
                @if($call->service_type || $call->extracted_service_type)
                <div>
                    <dt class="text-sm font-medium text-gray-500">Service</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $call->service_type ?? $call->extracted_service_type }}</dd>
                </div>
                @endif
                
                @if($call->appointment_reason || $call->extracted_appointment_reason)
                <div>
                    <dt class="text-sm font-medium text-gray-500">Reason</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $call->appointment_reason ?? $call->extracted_appointment_reason }}</dd>
                </div>
                @endif
            </div>
        </div>
        @endif

        {{-- Cost Breakdown (June Feature) --}}
        @if($call->total_cost || $call->llm_cost || $call->stt_cost || $call->tts_cost || $call->vapi_cost || $call->retell_llm_cost)
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold mb-4 flex items-center">
                <svg class="w-5 h-5 mr-2 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                Cost Analysis
            </h2>
            
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                @if($call->llm_cost || $call->retell_llm_cost)
                <div class="bg-blue-50 p-3 rounded">
                    <dt class="text-xs font-medium text-blue-700">LLM Cost</dt>
                    <dd class="mt-1 text-lg font-semibold text-blue-900">${{ number_format($call->llm_cost ?? $call->retell_llm_cost ?? 0, 4) }}</dd>
                </div>
                @endif
                
                @if($call->stt_cost || $call->retell_stt_cost)
                <div class="bg-green-50 p-3 rounded">
                    <dt class="text-xs font-medium text-green-700">STT Cost</dt>
                    <dd class="mt-1 text-lg font-semibold text-green-900">${{ number_format($call->stt_cost ?? $call->retell_stt_cost ?? 0, 4) }}</dd>
                </div>
                @endif
                
                @if($call->tts_cost || $call->retell_tts_cost)
                <div class="bg-purple-50 p-3 rounded">
                    <dt class="text-xs font-medium text-purple-700">TTS Cost</dt>
                    <dd class="mt-1 text-lg font-semibold text-purple-900">${{ number_format($call->tts_cost ?? $call->retell_tts_cost ?? 0, 4) }}</dd>
                </div>
                @endif
                
                @if($call->total_cost || $call->vapi_cost || $call->retell_total_cost)
                <div class="bg-yellow-50 p-3 rounded">
                    <dt class="text-xs font-medium text-yellow-700">Total Cost</dt>
                    <dd class="mt-1 text-lg font-semibold text-yellow-900">${{ number_format($call->total_cost ?? $call->vapi_cost ?? $call->retell_total_cost ?? 0, 4) }}</dd>
                </div>
                @endif
            </div>
        </div>
        @endif

        {{-- Transcript Section --}}
        @if($call->transcript || $call->transcript_text || $call->transcript_object)
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold mb-4 flex items-center">
                <svg class="w-5 h-5 mr-2 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                Call Transcript
            </h2>
            
            <div class="bg-gray-50 rounded-lg p-4 max-h-96 overflow-y-auto">
                @php
                    $transcriptContent = $call->transcript ?? $call->transcript_text ?? '';
                    if (empty($transcriptContent) && $call->transcript_object) {
                        $transcriptObj = is_string($call->transcript_object) ? json_decode($call->transcript_object, true) : $call->transcript_object;
                        if (is_array($transcriptObj)) {
                            $transcriptContent = collect($transcriptObj)->map(function($item) {
                                $role = $item['role'] ?? 'Unknown';
                                $content = $item['content'] ?? '';
                                return "<strong>{$role}:</strong> {$content}";
                            })->implode('<br><br>');
                        }
                    }
                @endphp
                
                @if($transcriptContent)
                    {!! nl2br(e($transcriptContent)) !!}
                @else
                    <p class="text-gray-500">No transcript available</p>
                @endif
            </div>
        </div>
        @endif

        {{-- Customer Journey Stats (June Feature) --}}
        @if($call->no_show_count || $call->reschedule_count || $call->is_first_visit)
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold mb-4 flex items-center">
                <svg class="w-5 h-5 mr-2 text-teal-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
                Customer Journey Statistics
            </h2>
            
            <div class="grid grid-cols-3 gap-4">
                <div class="text-center">
                    <div class="text-3xl font-bold text-gray-900">{{ $call->no_show_count ?? 0 }}</div>
                    <div class="text-sm text-gray-500">No Shows</div>
                </div>
                
                <div class="text-center">
                    <div class="text-3xl font-bold text-gray-900">{{ $call->reschedule_count ?? 0 }}</div>
                    <div class="text-sm text-gray-500">Reschedules</div>
                </div>
                
                <div class="text-center">
                    <div class="text-3xl font-bold {{ $call->is_first_visit ? 'text-green-600' : 'text-gray-900' }}">
                        {{ $call->is_first_visit ? 'Yes' : 'No' }}
                    </div>
                    <div class="text-sm text-gray-500">First Visit</div>
                </div>
            </div>
        </div>
        @endif

        {{-- Technical Details --}}
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold mb-4 flex items-center">
                <svg class="w-5 h-5 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
                Technical Information
            </h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @if($call->call_id)
                <div>
                    <dt class="text-sm font-medium text-gray-500">Call ID</dt>
                    <dd class="mt-1 text-sm text-gray-900 font-mono">{{ $call->call_id }}</dd>
                </div>
                @endif
                
                @if($call->retell_call_id)
                <div>
                    <dt class="text-sm font-medium text-gray-500">Retell ID</dt>
                    <dd class="mt-1 text-sm text-gray-900 font-mono">{{ $call->retell_call_id }}</dd>
                </div>
                @endif
                
                @if($call->agent_id)
                <div>
                    <dt class="text-sm font-medium text-gray-500">Agent ID</dt>
                    <dd class="mt-1 text-sm text-gray-900 font-mono">{{ $call->agent_id }}</dd>
                </div>
                @endif
                
                @if($call->call_status)
                <div>
                    <dt class="text-sm font-medium text-gray-500">Status</dt>
                    <dd class="mt-1">
                        <span class="px-2 py-1 text-xs font-medium rounded-full 
                            {{ $call->call_status == 'ended' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                            {{ $call->call_status }}
                        </span>
                    </dd>
                </div>
                @endif
                
                @if($call->end_reason)
                <div>
                    <dt class="text-sm font-medium text-gray-500">End Reason</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $call->end_reason }}</dd>
                </div>
                @endif
                
                @if($call->latency_ms)
                <div>
                    <dt class="text-sm font-medium text-gray-500">Latency</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $call->latency_ms }}ms</dd>
                </div>
                @endif
            </div>
        </div>

        {{-- Raw Data Debug (Remove in production) --}}
        @if(config('app.debug'))
        <details class="bg-gray-100 rounded-lg p-4">
            <summary class="cursor-pointer font-medium">Debug: All Available Fields</summary>
            <pre class="mt-2 text-xs overflow-x-auto">{{ json_encode($call->toArray(), JSON_PRETTY_PRINT) }}</pre>
        </details>
        @endif
    </div>
</x-filament-panels::page>