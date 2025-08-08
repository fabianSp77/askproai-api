@php
    $state = $getState();
    $recordingUrl = $state['recording_url'] ?? null;
    $callId = $state['call_id'] ?? null;
    $duration = $state['duration'] ?? 0;
@endphp

<div class="w-full">
    @if($recordingUrl)
        <div class="bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center space-x-2">
                    <x-heroicon-m-speaker-wave class="w-5 h-5 text-gray-500" />
                    <span class="text-sm font-medium text-gray-900 dark:text-gray-100">Anrufaufzeichnung</span>
                </div>
                @if($duration > 0)
                    <span class="text-xs text-gray-500 bg-gray-100 dark:bg-gray-800 px-2 py-1 rounded">
                        {{ gmdate('i:s', $duration) }} Min
                    </span>
                @endif
            </div>
            
            <div class="audio-player-container">
                <audio 
                    controls 
                    preload="metadata" 
                    class="w-full h-10 bg-gray-50 dark:bg-gray-800 rounded"
                    style="width: 100%;"
                >
                    <source src="{{ $recordingUrl }}" type="audio/mpeg">
                    <source src="{{ $recordingUrl }}" type="audio/wav">
                    <source src="{{ $recordingUrl }}" type="audio/mp4">
                    <p class="text-sm text-gray-500">
                        Ihr Browser unterstützt das Audio-Element nicht. 
                        <a href="{{ $recordingUrl }}" target="_blank" class="text-blue-600 hover:text-blue-800 underline">
                            Aufnahme herunterladen
                        </a>
                    </p>
                </audio>
            </div>
            
            <div class="mt-3 flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <button 
                        type="button"
                        onclick="navigator.clipboard.writeText('{{ $recordingUrl }}')"
                        class="inline-flex items-center px-2.5 py-1.5 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700"
                    >
                        <x-heroicon-m-clipboard class="w-4 h-4 mr-1" />
                        URL kopieren
                    </button>
                    
                    <a 
                        href="{{ $recordingUrl }}" 
                        target="_blank"
                        download="call_{{ $callId }}_recording.mp3"
                        class="inline-flex items-center px-2.5 py-1.5 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700"
                    >
                        <x-heroicon-m-arrow-down-tray class="w-4 h-4 mr-1" />
                        Download
                    </a>
                </div>
                
                <div class="text-xs text-gray-500">
                    Call ID: {{ $callId }}
                </div>
            </div>
        </div>
        
        <style>
            .audio-player-container audio {
                height: 40px;
                background: #f9fafb;
            }
            
            .dark .audio-player-container audio {
                background: #1f2937;
                border: 1px solid #374151;
            }
            
            .audio-player-container audio::-webkit-media-controls-panel {
                background-color: #f9fafb;
            }
            
            .dark .audio-player-container audio::-webkit-media-controls-panel {
                background-color: #1f2937;
            }
        </style>
    @else
        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg border-2 border-dashed border-gray-300 dark:border-gray-600 p-4">
            <div class="text-center">
                <x-heroicon-o-speaker-x-mark class="w-8 h-8 text-gray-400 mx-auto mb-2" />
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Keine Aufnahme verfügbar
                </p>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                    Die Aufnahme wurde möglicherweise nicht gespeichert oder ist noch nicht verfügbar.
                </p>
            </div>
        </div>
    @endif
</div>