<div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
    <div class="fi-section-header flex items-center gap-x-3 px-6 py-4">
        <div class="fi-section-header-heading flex-1">
            <h3 class="fi-section-header-title text-base font-semibold leading-6 text-gray-950 dark:text-white">
                Aufnahme
            </h3>
            <p class="fi-section-header-description text-sm text-gray-600 dark:text-gray-400">
                Anrufaufzeichnung anhören
            </p>
        </div>
    </div>
    <div class="fi-section-content-ctn border-t border-gray-200 dark:border-white/10">
        <div class="fi-section-content p-6">
            <div class="audio-player-container">
                <audio controls preload="metadata" class="w-full h-10 bg-gray-50 dark:bg-gray-800 rounded" style="width: 100%;">
                    <source src="{{ $recording_url }}" type="audio/mpeg">
                    <source src="{{ $recording_url }}" type="audio/wav">
                    <source src="{{ $recording_url }}" type="audio/mp4">
                    <p class="text-sm text-gray-500">
                        Ihr Browser unterstützt das Audio-Element nicht. 
                        <a href="{{ $recording_url }}" target="_blank" class="text-blue-600 hover:text-blue-800 underline">
                            Aufnahme herunterladen
                        </a>
                    </p>
                </audio>
                
                <div class="mt-3 flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <a href="{{ $recording_url }}" target="_blank" download="call_{{ $call_id }}_recording.mp3"
                           class="inline-flex items-center px-2.5 py-1.5 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            Download
                        </a>
                    </div>
                    
                    <div class="text-xs text-gray-500">
                        @if($duration > 0)
                            Dauer: {{ gmdate('i:s', $duration) }} Min | 
                        @endif
                        Call ID: {{ $call_id }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>