<div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 sm:p-6">
    <div class="space-y-4">
        {{-- Header --}}
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-blue-100 dark:bg-blue-900 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                        Anrufaufnahme
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Dauer: {{ gmdate('H:i:s', $duration) }}
                    </p>
                </div>
            </div>

            {{-- Download Button --}}
            <a href="{{ $url }}"
               download="anruf_{{ $callId }}_{{ date('Y-m-d_His') }}.mp3"
               class="inline-flex items-center gap-2 px-3 sm:px-4 py-2 w-full sm:w-auto justify-center sm:justify-start
                      text-sm font-medium text-gray-700 dark:text-gray-300
                      bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600
                      rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600
                      focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500
                      transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10" />
                </svg>
                Herunterladen
            </a>
        </div>

        {{-- Audio Player --}}
        <div class="relative">
            <audio id="audio-{{ $callId }}"
                   class="w-full focus:outline-none"
                   controls
                   preload="metadata">
                <source src="{{ $url }}" type="audio/mpeg">
                <source src="{{ $url }}" type="audio/wav">
                <source src="{{ $url }}" type="audio/ogg">
                Ihr Browser unterstützt keine Audio-Wiedergabe.
            </audio>
        </div>

        {{-- Player Controls Enhancement (optional) --}}
        <div class="flex items-center justify-between text-sm text-gray-600 dark:text-gray-400">
            <div class="flex items-center gap-4">
                {{-- Playback Speed --}}
                <div class="flex items-center gap-2">
                    <label for="speed-{{ $callId }}" class="text-xs">Geschwindigkeit:</label>
                    <select id="speed-{{ $callId }}"
                            onchange="document.getElementById('audio-{{ $callId }}').playbackRate = this.value"
                            class="text-xs border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded">
                        <option value="0.5">0.5x</option>
                        <option value="0.75">0.75x</option>
                        <option value="1" selected>1x</option>
                        <option value="1.25">1.25x</option>
                        <option value="1.5">1.5x</option>
                        <option value="2">2x</option>
                    </select>
                </div>

                {{-- Skip Buttons --}}
                <div class="flex items-center gap-1">
                    <button onclick="skipAudio('{{ $callId }}', -10)"
                            class="p-1 hover:bg-gray-200 dark:hover:bg-gray-700 rounded"
                            title="10 Sekunden zurück">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M8.445 14.832A1 1 0 0010 14v-4a1 1 0 00-1.555-.832L5 11.528V9a1 1 0 00-1-1H2a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-2.528l3.445 2.36z" />
                            <path d="M11 5a1 1 0 011-1h6a1 1 0 011 1v10a1 1 0 01-1 1h-6a1 1 0 01-1-1V5z" />
                        </svg>
                    </button>
                    <button onclick="skipAudio('{{ $callId }}', 10)"
                            class="p-1 hover:bg-gray-200 dark:hover:bg-gray-700 rounded"
                            title="10 Sekunden vor">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M11.555 14.832A1 1 0 0110 14v-4a1 1 0 011.555-.832L15 11.528V9a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-2.528l-3.445 2.36z" />
                            <path d="M1 5a1 1 0 011-1h6a1 1 0 011 1v10a1 1 0 01-1 1H2a1 1 0 01-1-1V5z" />
                        </svg>
                    </button>
                </div>
            </div>

            {{-- Keyboard Shortcuts Info --}}
            <div class="text-xs text-gray-500">
                <kbd class="px-1.5 py-0.5 bg-gray-100 dark:bg-gray-700 rounded">Space</kbd> Play/Pause
            </div>
        </div>
    </div>
</div>

<script>
function skipAudio(id, seconds) {
    const audio = document.getElementById(`audio-${id}`);
    if (audio) {
        audio.currentTime = Math.max(0, audio.currentTime + seconds);
    }
}

// Add keyboard shortcuts
document.addEventListener('DOMContentLoaded', function() {
    const audio = document.getElementById('audio-{{ $callId }}');
    if (audio) {
        document.addEventListener('keydown', function(e) {
            if (e.code === 'Space' && e.target.tagName !== 'INPUT' && e.target.tagName !== 'TEXTAREA') {
                e.preventDefault();
                if (audio.paused) {
                    audio.play();
                } else {
                    audio.pause();
                }
            }
        });
    }
});
</script>