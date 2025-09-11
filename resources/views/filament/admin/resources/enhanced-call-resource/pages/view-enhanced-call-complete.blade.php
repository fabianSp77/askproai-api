<x-filament-panels::page>
    @php
        $call = $this->record;
        // Debug: Check if data exists
        $hasTranscript = !empty($call->transcript);
        $hasSummary = !empty($call->summary);
        
        $analysis = $call->analysis ?? [];
        $customData = $analysis['custom_analysis_data'] ?? [];
        
        // Extract key information
        $callerName = $customData['caller_full_name'] ?? 'Unknown';
        $companyName = $customData['company_name'] ?? 'N/A';
        $customerRequest = $customData['customer_request'] ?? 'No request recorded';
        $urgencyLevel = $customData['urgency_level'] ?? 'normal';
        $callbackRequested = $customData['callback_requested'] ?? false;
        $gdprConsent = $customData['gdpr_consent_given'] ?? false;
        
        // Call metrics
        $duration = $call->duration_sec ?? 0;
        $durationFormatted = sprintf('%d:%02d', floor($duration / 60), $duration % 60);
        $sentiment = $call->sentiment ?? $analysis['sentiment'] ?? 'neutral';
        $summary = $call->summary ?? $analysis['call_summary'] ?? 'No summary available';
        
        // Status and success
        $isSuccessful = $call->call_successful ?? false;
        $callStatus = $call->call_status ?? 'unknown';
        $disconnectReason = $call->disconnection_reason ?? $call->disconnect_reason ?? 'unknown';
        
        // Audio and transcript
        $recordingUrl = $call->recording_url ?? null;
        $transcript = $call->transcript ?? '';
        $publicLogUrl = $call->public_log_url ?? null;
        
        // Language detection
        $detectedLanguage = $call->detected_language ?? 'unknown';
        $languageConfidence = $call->language_confidence ?? 0;
        
        // Cost calculation
        $costCents = $call->cost_cents ?? 0;
        $costFormatted = number_format($costCents / 100, 2);
        
        // Urgency styling
        $urgencyColor = match($urgencyLevel) {
            'dringend' => 'red',
            'schnell' => 'orange',
            'normal' => 'gray',
            default => 'gray'
        };
        
        // Sentiment styling
        $sentimentColor = match(strtolower($sentiment)) {
            'positive' => 'green',
            'negative' => 'red',
            'neutral' => 'gray',
            default => 'gray'
        };
    @endphp


    {{-- Layer 1: Critical Overview Bar --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 mb-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-6">
                {{-- Status Badge --}}
                <div class="flex items-center">
                    @if($isSuccessful)
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                            <svg class="w-4 h-4 mr-1.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            Successful Call
                        </span>
                    @else
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">
                            <svg class="w-4 h-4 mr-1.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                            Failed Call
                        </span>
                    @endif
                </div>

                {{-- Duration --}}
                <div class="flex items-center text-gray-600">
                    <svg class="w-5 h-5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="font-medium">{{ $durationFormatted }}</span>
                </div>

                {{-- Cost --}}
                <div class="flex items-center text-gray-600">
                    <svg class="w-5 h-5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="font-medium">€{{ $costFormatted }}</span>
                </div>

                {{-- Sentiment --}}
                <div class="flex items-center">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-{{ $sentimentColor }}-100 text-{{ $sentimentColor }}-800">
                        @if($sentiment === 'positive')
                            😊 Positive
                        @elseif($sentiment === 'negative')
                            😔 Negative
                        @else
                            😐 Neutral
                        @endif
                    </span>
                </div>

                {{-- Urgency --}}
                @if($urgencyLevel !== 'normal')
                    <div class="flex items-center">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-{{ $urgencyColor }}-100 text-{{ $urgencyColor }}-800">
                            <svg class="w-4 h-4 mr-1.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                            {{ ucfirst($urgencyLevel) }}
                        </span>
                    </div>
                @endif
            </div>

            {{-- Quick Actions --}}
            <div class="flex items-center space-x-2">
                @if($callbackRequested)
                    <button class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                        Call Back
                    </button>
                @endif
                
                <button class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    Create Appointment
                </button>

                <button class="inline-flex items-center px-4 py-2 bg-gray-600 text-white text-sm font-medium rounded-lg hover:bg-gray-700">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    Send Email
                </button>
            </div>
        </div>
    </div>

    {{-- Layer 2: Smart Summary Section --}}
    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-gray-800 dark:to-gray-700 rounded-lg p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">AI-Generated Summary</h2>
        <p class="text-gray-700 dark:text-gray-300 leading-relaxed">{{ $summary }}</p>
        
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6">
            {{-- Caller Info --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4">
                <p class="text-sm text-gray-500 dark:text-gray-400">Caller</p>
                <p class="font-semibold text-gray-900 dark:text-white">{{ $callerName }}</p>
                <p class="text-sm text-gray-600 dark:text-gray-300">{{ $companyName }}</p>
            </div>

            {{-- Contact --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4">
                <p class="text-sm text-gray-500 dark:text-gray-400">Phone</p>
                <p class="font-semibold text-gray-900 dark:text-white">{{ $call->from_number ?? 'N/A' }}</p>
                <p class="text-sm text-gray-600 dark:text-gray-300">Customer #{{ $customData['customer_number'] ?? 'N/A' }}</p>
            </div>

            {{-- Request --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4">
                <p class="text-sm text-gray-500 dark:text-gray-400">Request</p>
                <p class="font-semibold text-gray-900 dark:text-white text-sm">{{ Str::limit($customerRequest, 50) }}</p>
            </div>

            {{-- GDPR & Callback --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4">
                <p class="text-sm text-gray-500 dark:text-gray-400">Compliance</p>
                <div class="flex items-center space-x-2 mt-1">
                    @if($gdprConsent)
                        <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-800">
                            ✓ GDPR
                        </span>
                    @endif
                    @if($callbackRequested)
                        <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-blue-100 text-blue-800">
                            📞 Callback
                        </span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Layer 3: Interactive Content Area (3-Column Layout) --}}
    <div class="grid grid-cols-12 gap-6">
        {{-- Left Column: Customer Context --}}
        <div class="col-span-3">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Customer Profile</h3>
                
                {{-- Customer Avatar --}}
                <div class="flex items-center mb-4">
                    <div class="w-16 h-16 bg-gray-200 rounded-full flex items-center justify-center">
                        <span class="text-2xl font-bold text-gray-600">{{ substr($callerName, 0, 1) }}</span>
                    </div>
                    <div class="ml-4">
                        <p class="font-semibold text-gray-900 dark:text-white">{{ $callerName }}</p>
                        <p class="text-sm text-gray-600 dark:text-gray-300">{{ $companyName }}</p>
                    </div>
                </div>

                {{-- Contact Details --}}
                <div class="space-y-3 border-t pt-4">
                    <div class="flex items-center text-sm">
                        <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                        <span class="text-gray-700 dark:text-gray-300">{{ $call->from_number ?? 'No phone' }}</span>
                    </div>
                    
                    @if($customData['caller_email'] ?? false)
                        <div class="flex items-center text-sm">
                            <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            <span class="text-gray-700 dark:text-gray-300">{{ $customData['caller_email'] }}</span>
                        </div>
                    @endif
                </div>

                {{-- Previous Calls --}}
                <div class="mt-6 border-t pt-4">
                    <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Recent Interactions</h4>
                    <div class="space-y-2">
                        <div class="text-sm text-gray-600">
                            <p class="font-medium">This is the first call</p>
                            <p class="text-xs text-gray-500">No previous history</p>
                        </div>
                    </div>
                </div>

                {{-- Cal.com Appointments --}}
                @if($call->appointment)
                    <div class="mt-6 border-t pt-4">
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Appointments</h4>
                        <div class="bg-blue-50 rounded p-3">
                            <p class="text-sm font-medium text-blue-900">Upcoming</p>
                            <p class="text-xs text-blue-700">{{ $call->appointment->starts_at?->format('M d, Y g:i A') ?? 'TBD' }}</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Center Column: Call Details --}}
        <div class="col-span-6">
            {{-- Audio Player --}}
            @if($recordingUrl)
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Call Recording</h3>
                    <audio controls class="w-full">
                        <source src="{{ $recordingUrl }}" type="audio/wav">
                        Your browser does not support the audio element.
                    </audio>
                    
                    {{-- Waveform placeholder --}}
                    <div class="mt-4 h-16 bg-gray-100 dark:bg-gray-700 rounded flex items-center justify-center">
                        <span class="text-sm text-gray-500">Waveform visualization</span>
                    </div>
                </div>
            @endif

            {{-- Searchable Transcript --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Transcript</h3>
                    <div class="relative">
                        <input type="text" 
                               placeholder="Search transcript..." 
                               class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <svg class="w-5 h-5 absolute left-3 top-2.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                </div>

                <div class="space-y-4 max-h-96 overflow-y-auto">
                    @if($transcript)
                        @php
                            $lines = explode("\n", $transcript);
                        @endphp
                        @foreach($lines as $line)
                            @if(str_starts_with($line, 'Agent:'))
                                <div class="flex">
                                    <div class="flex-shrink-0 w-20">
                                        <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                            Agent
                                        </span>
                                    </div>
                                    <div class="flex-1 ml-3">
                                        <p class="text-sm text-gray-700 dark:text-gray-300">
                                            {{ substr($line, 7) }}
                                        </p>
                                    </div>
                                </div>
                            @elseif(str_starts_with($line, 'User:'))
                                <div class="flex">
                                    <div class="flex-shrink-0 w-20">
                                        <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-800">
                                            User
                                        </span>
                                    </div>
                                    <div class="flex-1 ml-3">
                                        <p class="text-sm text-gray-700 dark:text-gray-300">
                                            {{ substr($line, 6) }}
                                        </p>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    @else
                        <p class="text-gray-500">No transcript available</p>
                    @endif
                </div>
            </div>
        </div>

        {{-- Right Column: Analytics & Actions --}}
        <div class="col-span-3">
            {{-- Language Detection --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Language Analysis</h3>
                <div class="space-y-3">
                    <div>
                        <p class="text-sm text-gray-500">Detected Language</p>
                        <p class="font-semibold text-gray-900 dark:text-white">
                            {{ strtoupper($detectedLanguage) }} 
                            <span class="text-sm text-gray-500">({{ number_format($languageConfidence * 100, 0) }}% confidence)</span>
                        </p>
                    </div>
                </div>
            </div>

            {{-- Call Metrics --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Call Metrics</h3>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-500">Status</span>
                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ ucfirst($callStatus) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-500">Disconnect</span>
                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ ucfirst(str_replace('_', ' ', $disconnectReason)) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-500">Agent ID</span>
                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ Str::limit($call->agent_id ?? 'N/A', 20) }}</span>
                    </div>
                </div>
            </div>

            {{-- Action Recommendations --}}
            <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-6">
                <h3 class="text-lg font-semibold text-yellow-900 dark:text-yellow-200 mb-4">Recommended Actions</h3>
                <ul class="space-y-2">
                    @if($callbackRequested)
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-yellow-600 mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-8.293l-3-3a1 1 0 00-1.414 1.414L10.586 9H7a1 1 0 100 2h3.586l-1.293 1.293a1 1 0 101.414 1.414l3-3a1 1 0 000-1.414z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-sm text-yellow-800 dark:text-yellow-200">Schedule callback ASAP</span>
                        </li>
                    @endif
                    @if($urgencyLevel === 'dringend')
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-yellow-600 mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-8.293l-3-3a1 1 0 00-1.414 1.414L10.586 9H7a1 1 0 100 2h3.586l-1.293 1.293a1 1 0 101.414 1.414l3-3a1 1 0 000-1.414z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-sm text-yellow-800 dark:text-yellow-200">Prioritize - Urgent request</span>
                        </li>
                    @endif
                    @if(!$call->appointment)
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-yellow-600 mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-8.293l-3-3a1 1 0 00-1.414 1.414L10.586 9H7a1 1 0 100 2h3.586l-1.293 1.293a1 1 0 101.414 1.414l3-3a1 1 0 000-1.414z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-sm text-yellow-800 dark:text-yellow-200">Create appointment</span>
                        </li>
                    @endif
                </ul>
            </div>
        </div>
    </div>

    {{-- Layer 4: Technical & Extended Data (Collapsible) --}}
    <div class="mt-6">
        <details class="bg-gray-50 dark:bg-gray-800 rounded-lg">
            <summary class="cursor-pointer p-4 font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
                Technical Details & Debug Information
            </summary>
            <div class="p-4 border-t border-gray-200 dark:border-gray-700">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <h4 class="font-semibold text-sm text-gray-700 dark:text-gray-300 mb-2">Call Identifiers</h4>
                        <dl class="space-y-1 text-sm">
                            <div class="flex">
                                <dt class="text-gray-500 w-32">Call ID:</dt>
                                <dd class="text-gray-900 dark:text-white font-mono">{{ $call->call_id ?? 'N/A' }}</dd>
                            </div>
                            <div class="flex">
                                <dt class="text-gray-500 w-32">Conversation ID:</dt>
                                <dd class="text-gray-900 dark:text-white font-mono">{{ $call->conversation_id ?? 'N/A' }}</dd>
                            </div>
                            <div class="flex">
                                <dt class="text-gray-500 w-32">External ID:</dt>
                                <dd class="text-gray-900 dark:text-white font-mono">{{ $call->external_id ?? 'N/A' }}</dd>
                            </div>
                        </dl>
                    </div>
                    
                    <div>
                        <h4 class="font-semibold text-sm text-gray-700 dark:text-gray-300 mb-2">Timestamps</h4>
                        <dl class="space-y-1 text-sm">
                            <div class="flex">
                                <dt class="text-gray-500 w-32">Started:</dt>
                                <dd class="text-gray-900 dark:text-white">{{ $call->start_timestamp ?? 'N/A' }}</dd>
                            </div>
                            <div class="flex">
                                <dt class="text-gray-500 w-32">Ended:</dt>
                                <dd class="text-gray-900 dark:text-white">{{ $call->end_timestamp ?? 'N/A' }}</dd>
                            </div>
                            <div class="flex">
                                <dt class="text-gray-500 w-32">Created:</dt>
                                <dd class="text-gray-900 dark:text-white">{{ $call->created_at }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>

                @if($publicLogUrl)
                    <div class="mt-4">
                        <a href="{{ $publicLogUrl }}" target="_blank" class="inline-flex items-center text-sm text-blue-600 hover:text-blue-800">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                            </svg>
                            View Public Log
                        </a>
                    </div>
                @endif

                {{-- Raw Analysis Data --}}
                @if(!empty($analysis))
                    <div class="mt-4">
                        <h4 class="font-semibold text-sm text-gray-700 dark:text-gray-300 mb-2">Raw Analysis Data</h4>
                        <pre class="bg-gray-900 text-green-400 p-4 rounded text-xs overflow-x-auto">{{ json_encode($analysis, JSON_PRETTY_PRINT) }}</pre>
                    </div>
                @endif
            </div>
        </details>
    </div>
</x-filament-panels::page>