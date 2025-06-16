<div class="w-full space-y-4">
    @if($audioUrl)
        <div class="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-6">
            <audio 
                controls 
                class="w-full mb-4" 
                id="audio-player-{{ $recordId }}"
                preload="metadata"
            >
                <source src="{{ $audioUrl }}" type="audio/mpeg">
                <source src="{{ $audioUrl }}" type="audio/wav">
                Ihr Browser unterstützt kein Audio.
            </audio>
            
            <!-- Playback Speed Controls -->
            <div class="flex items-center justify-between border-t dark:border-gray-700 pt-4">
                <div class="flex items-center gap-2">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Geschwindigkeit:</span>
                    <div class="flex gap-1">
                        @foreach([0.5, 0.75, 1, 1.25, 1.5, 2] as $speed)
                            <button 
                                onclick="document.getElementById('audio-player-{{ $recordId }}').playbackRate = {{ $speed }}"
                                class="px-3 py-1 text-sm rounded-md transition-colors
                                    {{ $speed == 1 ? 'bg-primary-600 text-white' : 'bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700' }}"
                                id="speed-btn-{{ $speed }}-{{ $recordId }}"
                            >
                                {{ $speed }}x
                            </button>
                        @endforeach
                    </div>
                </div>
                
                <div class="flex items-center gap-4">
                    <span class="text-sm text-gray-600 dark:text-gray-400">
                        <span class="font-medium">Dauer:</span> {{ gmdate('i:s', $duration) }} Min.
                    </span>
                    
                    <a 
                        href="{{ $audioUrl }}" 
                        download="anruf-{{ $recordId }}-{{ now()->format('Y-m-d') }}.mp3"
                        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 transition-colors"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        Download
                    </a>
                </div>
            </div>
            
            @if($publicLogUrl)
                <div class="border-t dark:border-gray-700 pt-4 mt-4">
                    <a 
                        href="{{ $publicLogUrl }}" 
                        target="_blank"
                        class="inline-flex items-center gap-2 text-sm text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                        </svg>
                        Öffentlichen Link öffnen
                    </a>
                </div>
            @endif
        </div>
        
        <script>
            // Update button styles when speed changes
            document.getElementById('audio-player-{{ $recordId }}').addEventListener('ratechange', function(e) {
                const currentRate = e.target.playbackRate;
                document.querySelectorAll('[id^="speed-btn-"][id$="-{{ $recordId }}"]').forEach(btn => {
                    const btnRate = parseFloat(btn.id.split('-')[2]);
                    if (btnRate === currentRate) {
                        btn.classList.add('bg-primary-600', 'text-white');
                        btn.classList.remove('bg-gray-100', 'dark:bg-gray-800', 'hover:bg-gray-200', 'dark:hover:bg-gray-700');
                    } else {
                        btn.classList.remove('bg-primary-600', 'text-white');
                        btn.classList.add('bg-gray-100', 'dark:bg-gray-800', 'hover:bg-gray-200', 'dark:hover:bg-gray-700');
                    }
                });
            });
        </script>
    @else
        <div class="text-center py-8 text-gray-500 dark:text-gray-400">
            <svg class="w-12 h-12 mx-auto mb-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                    d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
            </svg>
            <p>Keine Aufnahme verfügbar</p>
        </div>
    @endif
</div>