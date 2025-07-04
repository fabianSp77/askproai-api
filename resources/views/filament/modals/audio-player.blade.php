@php
    // Support both direct parameters and record object
    $audioUrl = $audioUrl ?? ($record->audio_url ?? $record->recording_url ?? null);
    $duration = $duration ?? ($record->duration_sec ?? 0);
    $callId = $callId ?? ($record->id ?? 'unknown');
    $transcript = $transcript ?? ($record->transcript ?? null);
    $createdAt = isset($record) ? ($record->start_timestamp ?? $record->created_at) : now();
@endphp

<div class="space-y-4">
    @if($audioUrl)
        <audio controls class="w-full" id="modal-audio-{{ $callId }}">
            <source src="{{ $audioUrl }}" type="audio/mpeg">
            Ihr Browser unterstützt kein Audio.
        </audio>
        
        <div class="grid grid-cols-4 gap-2">
            <button onclick="document.getElementById('modal-audio-{{ $callId }}').playbackRate = 0.5" 
                class="px-3 py-2 text-sm rounded-lg bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 transition">
                0.5x
            </button>
            <button onclick="document.getElementById('modal-audio-{{ $callId }}').playbackRate = 1" 
                class="px-3 py-2 text-sm rounded-lg bg-amber-600 text-white hover:bg-amber-700 transition">
                1x
            </button>
            <button onclick="document.getElementById('modal-audio-{{ $callId }}').playbackRate = 1.5" 
                class="px-3 py-2 text-sm rounded-lg bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 transition">
                1.5x
            </button>
            <button onclick="document.getElementById('modal-audio-{{ $callId }}').playbackRate = 2" 
                class="px-3 py-2 text-sm rounded-lg bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 transition">
                2x
            </button>
        </div>
        
        <div class="flex items-center justify-between pt-4 border-t dark:border-gray-700">
            <div class="text-sm text-gray-600 dark:text-gray-400">
                <span class="font-medium">Dauer:</span> {{ gmdate('i:s', $duration) }} Min.
            </div>
            <a href="{{ $audioUrl }}" 
                download="anruf-{{ $callId }}-{{ $createdAt->format('Y-m-d') }}.mp3" 
                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-amber-600 rounded-lg hover:bg-amber-700 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                </svg>
                Download
            </a>
        </div>
        
        @if($transcript)
            <div class="pt-4 border-t dark:border-gray-700">
                <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-2">Transkript</h4>
                <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4 max-h-64 overflow-y-auto">
                    <pre class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ $transcript }}</pre>
                </div>
            </div>
        @endif
    @else
        <div class="text-center py-8">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
            </svg>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Keine Aufzeichnung verfügbar</p>
        </div>
    @endif
</div>