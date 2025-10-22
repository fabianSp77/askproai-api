<div class="space-y-6">
    {{-- Call Metadata Header --}}
    <div class="rounded-lg bg-gray-50 dark:bg-gray-800 p-4">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
            <div>
                <div class="text-gray-500 dark:text-gray-400 text-xs uppercase tracking-wide">Dauer</div>
                <div class="font-semibold mt-1">
                    @if($call->duration_sec)
                        @php
                            $minutes = floor($call->duration_sec / 60);
                            $seconds = $call->duration_sec % 60;
                        @endphp
                        {{ $minutes > 0 ? "{$minutes}m {$seconds}s" : "{$seconds}s" }}
                    @else
                        -
                    @endif
                </div>
            </div>
            <div>
                <div class="text-gray-500 dark:text-gray-400 text-xs uppercase tracking-wide">Status</div>
                <div class="font-semibold mt-1">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                        @if($call->status === 'answered') bg-success-100 text-success-800 dark:bg-success-900 dark:text-success-200
                        @elseif($call->status === 'missed') bg-danger-100 text-danger-800 dark:bg-danger-900 dark:text-danger-200
                        @else bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200
                        @endif">
                        {{ ucfirst($call->status) }}
                    </span>
                </div>
            </div>
            <div>
                <div class="text-gray-500 dark:text-gray-400 text-xs uppercase tracking-wide">Von</div>
                <div class="font-semibold mt-1">{{ $call->from_number ?? '-' }}</div>
            </div>
            <div>
                <div class="text-gray-500 dark:text-gray-400 text-xs uppercase tracking-wide">An</div>
                <div class="font-semibold mt-1">{{ $call->to_number ?? '-' }}</div>
            </div>
        </div>

        @if($call->appointment_made && !$call->converted_appointment_id)
            <div class="mt-4 rounded-md bg-warning-50 dark:bg-warning-900/20 border border-warning-200 dark:border-warning-800 p-3">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-warning-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                    <div class="flex-1">
                        <div class="font-semibold text-warning-800 dark:text-warning-200">
                            Terminbuchung fehlgeschlagen
                        </div>
                        <div class="text-sm text-warning-600 dark:text-warning-400 mt-1">
                            Der Agent versuchte einen Termin zu buchen, aber die Buchung schlug fehl.
                        </div>
                    </div>
                </div>
            </div>
        @elseif($call->appointment_made && $call->converted_appointment_id)
            <div class="mt-4 rounded-md bg-success-50 dark:bg-success-900/20 border border-success-200 dark:border-success-800 p-3">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-success-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <div class="flex-1">
                        <div class="font-semibold text-success-800 dark:text-success-200">
                            Termin erfolgreich gebucht
                        </div>
                        <div class="text-sm text-success-600 dark:text-success-400 mt-1">
                            Termin ID: {{ $call->converted_appointment_id }}
                        </div>
                    </div>
                </div>
            </div>
        @endif

        @if($call->recording_url)
            <div class="mt-4">
                <a href="{{ $call->recording_url }}" target="_blank"
                   class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Aufnahme abspielen
                </a>
            </div>
        @endif
    </div>

    {{-- Transcript Content --}}
    <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
        <h3 class="text-lg font-semibold mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
            </svg>
            Gesprächsverlauf
        </h3>

        <div class="space-y-4 max-h-[500px] overflow-y-auto pr-2">
            @if($transcript_object && is_array($transcript_object))
                {{-- Structured transcript with timestamps --}}
                @foreach($transcript_object as $index => $message)
                    @php
                        $isAgent = ($message['role'] ?? 'agent') === 'agent';
                        $content = $message['content'] ?? '';
                        $timestamp = isset($message['words'][0]['start'])
                            ? gmdate("i:s", floor($message['words'][0]['start']))
                            : null;
                    @endphp

                    <div class="flex gap-3 {{ $isAgent ? 'justify-start' : 'justify-end' }}">
                        <div class="flex flex-col {{ $isAgent ? 'items-start' : 'items-end' }} max-w-[80%]">
                            <div class="flex items-center gap-2 mb-1">
                                @if($isAgent)
                                    <div class="flex items-center gap-1.5">
                                        <div class="w-2 h-2 rounded-full bg-primary-500"></div>
                                        <span class="text-xs font-medium text-gray-600 dark:text-gray-400">Agent</span>
                                    </div>
                                @else
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-xs font-medium text-gray-600 dark:text-gray-400">Kunde</span>
                                        <div class="w-2 h-2 rounded-full bg-success-500"></div>
                                    </div>
                                @endif
                                @if($timestamp)
                                    <span class="text-xs text-gray-400 dark:text-gray-500">{{ $timestamp }}</span>
                                @endif
                            </div>
                            <div class="rounded-lg px-4 py-2.5 {{ $isAgent
                                ? 'bg-primary-50 dark:bg-primary-900/20 text-primary-900 dark:text-primary-100'
                                : 'bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-gray-100' }}">
                                <p class="text-sm leading-relaxed">{{ $content }}</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            @elseif($transcript)
                {{-- Fallback: Plain text transcript --}}
                <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                    <div class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap font-mono">{{ $transcript }}</div>
                </div>
            @else
                <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                    <svg class="w-12 h-12 mx-auto mb-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                    </svg>
                    <div class="font-medium">Kein Transcript verfügbar</div>
                    <div class="text-sm mt-1">Für diesen Anruf wurde kein Gesprächsverlauf aufgezeichnet.</div>
                </div>
            @endif
        </div>
    </div>

    {{-- Summary Statistics --}}
    @if($transcript_object && is_array($transcript_object))
        <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
            <div class="grid grid-cols-3 gap-4 text-center">
                <div>
                    <div class="text-2xl font-bold text-primary-600 dark:text-primary-400">
                        {{ count(array_filter($transcript_object, fn($m) => ($m['role'] ?? 'agent') === 'agent')) }}
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Agent-Nachrichten</div>
                </div>
                <div>
                    <div class="text-2xl font-bold text-success-600 dark:text-success-400">
                        {{ count(array_filter($transcript_object, fn($m) => ($m['role'] ?? 'agent') === 'user')) }}
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Kunden-Nachrichten</div>
                </div>
                <div>
                    <div class="text-2xl font-bold text-gray-600 dark:text-gray-400">
                        {{ count($transcript_object) }}
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Gesamt</div>
                </div>
            </div>
        </div>
    @endif
</div>
