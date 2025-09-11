<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center space-x-2">
                <x-heroicon-o-speaker-wave class="w-5 h-5 text-primary-500" />
                <span>{{ $title }}</span>
            </div>
        </x-slot>

        @if($audioUrl)
            <div class="audio-player-container">
                <audio 
                    id="audio-player-{{ $callId }}" 
                    class="w-full"
                    controls
                    preload="metadata"
                >
                    <source src="{{ $audioUrl }}" type="audio/mpeg">
                    <source src="{{ $audioUrl }}" type="audio/wav">
                    <source src="{{ $audioUrl }}" type="audio/ogg">
                    Your browser does not support the audio element.
                </audio>

                <!-- Custom Controls -->
                <div class="mt-4 space-y-3">
                    <!-- Progress Bar -->
                    <div class="flex items-center space-x-3">
                        <span id="current-time-{{ $callId }}" class="text-sm text-gray-600 dark:text-gray-400">0:00</span>
                        <div class="flex-1 bg-gray-200 rounded-full h-2 dark:bg-gray-700 relative cursor-pointer" id="progress-bar-{{ $callId }}">
                            <div class="bg-primary-600 h-2 rounded-full transition-all duration-150" id="progress-{{ $callId }}" style="width: 0%"></div>
                        </div>
                        <span id="duration-{{ $callId }}" class="text-sm text-gray-600 dark:text-gray-400">0:00</span>
                    </div>

                    <!-- Control Buttons -->
                    <div class="flex items-center justify-center space-x-3">
                        <button 
                            type="button"
                            id="rewind-{{ $callId }}"
                            class="p-2 text-gray-600 hover:text-primary-600 transition-colors"
                            title="Rewind 10s"
                        >
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M8.445 14.832A1 1 0 0010 14v-2.798l5.445 3.63A1 1 0 0017 14V6a1 1 0 00-1.555-.832L10 8.798V6a1 1 0 00-1.555-.832l-6 4a1 1 0 000 1.664l6 4z"/>
                            </svg>
                        </button>

                        <button 
                            type="button"
                            id="play-pause-{{ $callId }}"
                            class="p-3 bg-primary-600 hover:bg-primary-700 text-white rounded-full transition-colors"
                        >
                            <svg id="play-icon-{{ $callId }}" class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd"/>
                            </svg>
                            <svg id="pause-icon-{{ $callId }}" class="w-6 h-6 hidden" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zM7 8a1 1 0 012 0v4a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v4a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                        </button>

                        <button 
                            type="button"
                            id="forward-{{ $callId }}"
                            class="p-2 text-gray-600 hover:text-primary-600 transition-colors"
                            title="Forward 10s"
                        >
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M4.555 5.168A1 1 0 003 6v8a1 1 0 001.555.832L10 11.202V14a1 1 0 001.555.832l6-4a1 1 0 000-1.664l-6-4A1 1 0 0010 6v2.798L4.555 5.168z"/>
                            </svg>
                        </button>
                    </div>

                    <!-- Speed Control -->
                    <div class="flex items-center justify-center space-x-2">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Speed:</span>
                        <select 
                            id="speed-{{ $callId }}" 
                            class="text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700"
                        >
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

            <script>
                (function() {
                    const audio = document.getElementById('audio-player-{{ $callId }}');
                    const playPauseBtn = document.getElementById('play-pause-{{ $callId }}');
                    const playIcon = document.getElementById('play-icon-{{ $callId }}');
                    const pauseIcon = document.getElementById('pause-icon-{{ $callId }}');
                    const progressBar = document.getElementById('progress-bar-{{ $callId }}');
                    const progress = document.getElementById('progress-{{ $callId }}');
                    const currentTimeEl = document.getElementById('current-time-{{ $callId }}');
                    const durationEl = document.getElementById('duration-{{ $callId }}');
                    const rewindBtn = document.getElementById('rewind-{{ $callId }}');
                    const forwardBtn = document.getElementById('forward-{{ $callId }}');
                    const speedSelect = document.getElementById('speed-{{ $callId }}');

                    function formatTime(seconds) {
                        const minutes = Math.floor(seconds / 60);
                        const secs = Math.floor(seconds % 60);
                        return `${minutes}:${secs.toString().padStart(2, '0')}`;
                    }

                    // Play/Pause
                    playPauseBtn.addEventListener('click', () => {
                        if (audio.paused) {
                            audio.play();
                            playIcon.classList.add('hidden');
                            pauseIcon.classList.remove('hidden');
                        } else {
                            audio.pause();
                            playIcon.classList.remove('hidden');
                            pauseIcon.classList.add('hidden');
                        }
                    });

                    // Update progress
                    audio.addEventListener('timeupdate', () => {
                        const percent = (audio.currentTime / audio.duration) * 100;
                        progress.style.width = percent + '%';
                        currentTimeEl.textContent = formatTime(audio.currentTime);
                    });

                    // Set duration
                    audio.addEventListener('loadedmetadata', () => {
                        durationEl.textContent = formatTime(audio.duration);
                    });

                    // Seek
                    progressBar.addEventListener('click', (e) => {
                        const rect = progressBar.getBoundingClientRect();
                        const percent = (e.clientX - rect.left) / rect.width;
                        audio.currentTime = percent * audio.duration;
                    });

                    // Rewind/Forward
                    rewindBtn.addEventListener('click', () => {
                        audio.currentTime = Math.max(0, audio.currentTime - 10);
                    });

                    forwardBtn.addEventListener('click', () => {
                        audio.currentTime = Math.min(audio.duration, audio.currentTime + 10);
                    });

                    // Speed control
                    speedSelect.addEventListener('change', () => {
                        audio.playbackRate = parseFloat(speedSelect.value);
                    });

                    // Reset icon when ended
                    audio.addEventListener('ended', () => {
                        playIcon.classList.remove('hidden');
                        pauseIcon.classList.add('hidden');
                    });
                })();
            </script>
        @else
            <div class="text-center py-8">
                <x-heroicon-o-speaker-x-mark class="w-12 h-12 text-gray-400 mx-auto mb-3" />
                <p class="text-gray-500 dark:text-gray-400">No recording available for this call</p>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>