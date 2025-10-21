@php
    $recordingUrl = $getRecord()->recording_url;
    $duration = $getRecord()->duration_sec ?? 0;
    $callId = $getRecord()->id;
@endphp

@if($recordingUrl)
<div class="audio-player-container bg-gray-50 dark:bg-gray-800 rounded-lg p-4 space-y-3">
    {{-- Header mit Titel --}}
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-2">
            <span class="text-lg">🎵</span>
            <h4 class="font-medium text-gray-900 dark:text-gray-100">Anrufaufnahme</h4>
            <span class="text-sm text-gray-500 dark:text-gray-400">
                ({{ gmdate('H:i:s', $duration) }})
            </span>
        </div>

        {{-- Download Button --}}
        <a href="{{ $recordingUrl }}"
           download="anruf_{{ $callId }}_{{ date('Y-m-d_His') }}.mp3"
           class="inline-flex items-center gap-2 px-3 py-1.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"></path>
            </svg>
            Herunterladen
        </a>
    </div>

    {{-- Audio Player --}}
    <div class="audio-player-wrapper">
        <audio id="audio-player-{{ $callId }}"
               class="hidden"
               data-call-id="{{ $callId }}"
               preload="metadata">
            <source src="{{ $recordingUrl }}" type="audio/mpeg">
            <source src="{{ $recordingUrl }}" type="audio/wav">
            Ihr Browser unterstützt keine Audio-Wiedergabe.
        </audio>

        {{-- Custom Controls --}}
        <div class="player-controls bg-white dark:bg-gray-700 rounded-lg p-4 shadow-sm">
            {{-- Play/Pause Button & Timeline --}}
            <div class="flex items-center gap-3">
                {{-- Play/Pause --}}
                <button type="button"
                        id="play-pause-{{ $callId }}"
                        class="play-pause-btn flex-shrink-0 w-12 h-12 flex items-center justify-center bg-primary-600 hover:bg-primary-700 text-white rounded-full transition-all transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2">
                    {{-- Play Icon --}}
                    <svg class="play-icon w-5 h-5 ml-1" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M6.3 2.841A1.5 1.5 0 004 4.11V15.89a1.5 1.5 0 002.3 1.269l9.344-5.89a1.5 1.5 0 000-2.538L6.3 2.84z"></path>
                    </svg>
                    {{-- Pause Icon (hidden by default) --}}
                    <svg class="pause-icon w-5 h-5 hidden" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zM7 8a1 1 0 012 0v4a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v4a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                    </svg>
                </button>

                {{-- Progress Bar Container --}}
                <div class="flex-1 space-y-1">
                    {{-- Time Display --}}
                    <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400">
                        <span id="current-time-{{ $callId }}">00:00</span>
                        <span id="total-time-{{ $callId }}">{{ gmdate('i:s', $duration) }}</span>
                    </div>

                    {{-- Progress Bar --}}
                    <div class="relative">
                        <div class="progress-bar-bg w-full h-2 bg-gray-200 dark:bg-gray-600 rounded-full cursor-pointer"
                             id="progress-bar-{{ $callId }}">
                            <div class="progress-bar-fill h-full bg-primary-600 rounded-full relative transition-all duration-100"
                                 id="progress-fill-{{ $callId }}"
                                 style="width: 0%">
                                <span class="absolute right-0 top-1/2 transform translate-x-1/2 -translate-y-1/2 w-3 h-3 bg-primary-600 rounded-full shadow-lg"></span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Volume & Speed Controls --}}
                <div class="flex items-center gap-2">
                    {{-- Volume Control --}}
                    <div class="relative group">
                        <button type="button"
                                id="volume-btn-{{ $callId }}"
                                class="p-2 text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"></path>
                            </svg>
                        </button>

                        {{-- Volume Slider (appears on hover) --}}
                        <div class="volume-slider absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 p-2 bg-white dark:bg-gray-700 rounded-lg shadow-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all">
                            <input type="range"
                                   id="volume-slider-{{ $callId }}"
                                   min="0"
                                   max="100"
                                   value="100"
                                   class="h-24 w-2"
                                   orient="vertical">
                        </div>
                    </div>

                    {{-- Playback Speed --}}
                    <div class="relative">
                        <select id="speed-select-{{ $callId }}"
                                class="text-sm px-2 py-1 bg-gray-100 dark:bg-gray-600 border-0 rounded-md text-gray-700 dark:text-gray-300 focus:ring-2 focus:ring-primary-500">
                            <option value="0.5">0.5x</option>
                            <option value="0.75">0.75x</option>
                            <option value="1" selected>1x</option>
                            <option value="1.25">1.25x</option>
                            <option value="1.5">1.5x</option>
                            <option value="2">2x</option>
                        </select>
                    </div>
                </div>
            </div>

            {{-- Waveform Visualization (placeholder for future enhancement) --}}
            <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-600">
                <div class="waveform-container h-16 bg-gray-50 dark:bg-gray-800 rounded flex items-center justify-center">
                    <canvas id="waveform-{{ $callId }}" class="w-full h-full"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Player JavaScript --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    const callId = '{{ $callId }}';
    const audio = document.getElementById(`audio-player-${callId}`);
    const playPauseBtn = document.getElementById(`play-pause-${callId}`);
    const progressBar = document.getElementById(`progress-bar-${callId}`);
    const progressFill = document.getElementById(`progress-fill-${callId}`);
    const currentTimeEl = document.getElementById(`current-time-${callId}`);
    const totalTimeEl = document.getElementById(`total-time-${callId}`);
    const volumeSlider = document.getElementById(`volume-slider-${callId}`);
    const speedSelect = document.getElementById(`speed-select-${callId}`);
    const waveformCanvas = document.getElementById(`waveform-${callId}`);

    if (!audio || !playPauseBtn) return;

    // Format time helper
    function formatTime(seconds) {
        const mins = Math.floor(seconds / 60);
        const secs = Math.floor(seconds % 60);
        return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
    }

    // Play/Pause functionality
    playPauseBtn.addEventListener('click', function() {
        if (audio.paused) {
            audio.play();
            this.querySelector('.play-icon').classList.add('hidden');
            this.querySelector('.pause-icon').classList.remove('hidden');
        } else {
            audio.pause();
            this.querySelector('.play-icon').classList.remove('hidden');
            this.querySelector('.pause-icon').classList.add('hidden');
        }
    });

    // Update progress bar
    audio.addEventListener('timeupdate', function() {
        const progress = (audio.currentTime / audio.duration) * 100;
        progressFill.style.width = `${progress}%`;
        currentTimeEl.textContent = formatTime(audio.currentTime);
    });

    // Set total duration when metadata loads
    audio.addEventListener('loadedmetadata', function() {
        totalTimeEl.textContent = formatTime(audio.duration);
    });

    // Click on progress bar to seek
    progressBar.addEventListener('click', function(e) {
        const rect = this.getBoundingClientRect();
        const percent = (e.clientX - rect.left) / rect.width;
        audio.currentTime = percent * audio.duration;
    });

    // Volume control
    if (volumeSlider) {
        volumeSlider.addEventListener('input', function() {
            audio.volume = this.value / 100;
        });
    }

    // Playback speed control
    if (speedSelect) {
        speedSelect.addEventListener('change', function() {
            audio.playbackRate = parseFloat(this.value);
        });
    }

    // Reset button when audio ends
    audio.addEventListener('ended', function() {
        playPauseBtn.querySelector('.play-icon').classList.remove('hidden');
        playPauseBtn.querySelector('.pause-icon').classList.add('hidden');
        progressFill.style.width = '0%';
        currentTimeEl.textContent = '00:00';
    });

    // Draw simple waveform visualization
    if (waveformCanvas) {
        const ctx = waveformCanvas.getContext('2d');
        const width = waveformCanvas.width = waveformCanvas.offsetWidth;
        const height = waveformCanvas.height = waveformCanvas.offsetHeight;

        // Simple static waveform for visual appeal
        ctx.strokeStyle = '#9333ea';
        ctx.lineWidth = 2;
        ctx.beginPath();

        const bars = 100;
        for (let i = 0; i < bars; i++) {
            const barHeight = Math.random() * (height * 0.8) + (height * 0.1);
            const x = (width / bars) * i;
            ctx.moveTo(x, (height - barHeight) / 2);
            ctx.lineTo(x, (height + barHeight) / 2);
        }
        ctx.stroke();
    }
});
</script>

<style>
/* Custom volume slider styles */
input[type="range"][orient="vertical"] {
    writing-mode: bt-lr; /* IE */
    -webkit-appearance: slider-vertical; /* WebKit */
    width: 20px;
    height: 100px;
}

/* Progress bar hover effect */
.progress-bar-bg:hover .progress-bar-fill {
    background-color: rgb(124 58 237); /* primary-700 */
}

/* Smooth transitions */
.audio-player-container button {
    transition: all 0.2s ease;
}

/* Volume slider styles */
.volume-slider {
    transition: opacity 0.2s ease, visibility 0.2s ease;
}

/* Waveform animation on play */
.playing #waveform-{{ $callId }} {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 0.7; }
    50% { opacity: 1; }
}
</style>

@else
<div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 text-center text-gray-500 dark:text-gray-400">
    <svg class="w-12 h-12 mx-auto mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"></path>
    </svg>
    <p>Keine Aufnahme verfügbar</p>
</div>
@endif