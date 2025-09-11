<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <x-heroicon-o-microphone class="w-5 h-5 text-primary-500 animate-pulse" />
                    <span class="font-semibold">{{ $title }}</span>
                </div>
                @if($audioUrl)
                    <div class="flex items-center space-x-2 text-sm text-gray-500">
                        <x-heroicon-o-clock class="w-4 h-4" />
                        <span id="call-duration-{{ $callId }}">Loading...</span>
                    </div>
                @endif
            </div>
        </x-slot>

        @if($audioUrl)
            <div class="audio-player-container space-y-4">
                <!-- Waveform Visualization -->
                <div class="relative bg-gradient-to-r from-primary-50 to-primary-100 dark:from-gray-800 dark:to-gray-700 rounded-lg p-4">
                    <canvas 
                        id="waveform-{{ $callId }}" 
                        class="w-full h-24"
                        style="display: block;"
                    ></canvas>
                    <div class="absolute inset-0 flex items-center justify-center opacity-0 hover:opacity-100 transition-opacity">
                        <button 
                            type="button"
                            id="waveform-play-{{ $callId }}"
                            class="p-4 bg-white/90 dark:bg-gray-800/90 rounded-full shadow-lg"
                        >
                            <x-heroicon-o-play class="w-8 h-8 text-primary-600" />
                        </button>
                    </div>
                </div>

                <!-- Main Audio Element -->
                <audio 
                    id="audio-{{ $callId }}" 
                    class="hidden"
                    preload="metadata"
                >
                    <source src="{{ $audioUrl }}" type="audio/mpeg">
                    <source src="{{ $audioUrl }}" type="audio/wav">
                    <source src="{{ $audioUrl }}" type="audio/ogg">
                </audio>

                <!-- Custom Controls -->
                <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 space-y-4">
                    <!-- Time Display -->
                    <div class="flex items-center justify-between text-sm font-mono">
                        <span id="time-{{ $callId }}" class="text-primary-600 dark:text-primary-400">00:00</span>
                        <span id="total-{{ $callId }}" class="text-gray-500">00:00</span>
                    </div>

                    <!-- Progress Bar -->
                    <div class="relative">
                        <div class="h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                            <div 
                                id="progress-{{ $callId }}" 
                                class="h-full bg-gradient-to-r from-primary-500 to-primary-600 transition-all duration-100"
                                style="width: 0%"
                            ></div>
                        </div>
                        <input 
                            type="range" 
                            id="seek-{{ $callId }}" 
                            class="absolute inset-0 w-full h-2 opacity-0 cursor-pointer"
                            min="0" 
                            max="100" 
                            value="0"
                        >
                    </div>

                    <!-- Control Buttons -->
                    <div class="flex items-center justify-center space-x-4">
                        <button 
                            type="button"
                            id="skip-back-{{ $callId }}"
                            class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
                            title="Skip back 15s"
                        >
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span class="sr-only">Skip back 15s</span>
                        </button>

                        <button 
                            type="button"
                            id="play-{{ $callId }}"
                            class="p-4 bg-primary-600 hover:bg-primary-700 text-white rounded-full shadow-lg transform transition hover:scale-105"
                        >
                            <svg id="play-svg-{{ $callId }}" class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M6.3 2.841A1.5 1.5 0 004 4.11V15.89a1.5 1.5 0 002.3 1.269l9.344-5.89a1.5 1.5 0 000-2.538L6.3 2.84z" />
                            </svg>
                            <svg id="pause-svg-{{ $callId }}" class="w-8 h-8 hidden" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zM7 8a1 1 0 012 0v4a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v4a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                        </button>

                        <button 
                            type="button"
                            id="skip-forward-{{ $callId }}"
                            class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
                            title="Skip forward 15s"
                        >
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" transform="scale(-1, 1) translate(-24, 0)" />
                            </svg>
                            <span class="sr-only">Skip forward 15s</span>
                        </button>
                    </div>

                    <!-- Additional Controls -->
                    <div class="flex items-center justify-between pt-2 border-t border-gray-200 dark:border-gray-700">
                        <!-- Volume -->
                        <div class="flex items-center space-x-2">
                            <button 
                                type="button"
                                id="mute-{{ $callId }}"
                                class="p-1 hover:bg-gray-100 dark:hover:bg-gray-700 rounded transition-colors"
                            >
                                <x-heroicon-o-speaker-wave id="volume-icon-{{ $callId }}" class="w-5 h-5" />
                                <x-heroicon-o-speaker-x-mark id="mute-icon-{{ $callId }}" class="w-5 h-5 hidden" />
                            </button>
                            <input 
                                type="range" 
                                id="volume-{{ $callId }}" 
                                class="w-20 h-1 bg-gray-200 rounded-lg cursor-pointer"
                                min="0" 
                                max="100" 
                                value="100"
                            >
                        </div>

                        <!-- Playback Speed -->
                        <select 
                            id="rate-{{ $callId }}" 
                            class="text-sm px-2 py-1 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700"
                        >
                            <option value="0.5">0.5×</option>
                            <option value="0.75">0.75×</option>
                            <option value="1" selected>1×</option>
                            <option value="1.25">1.25×</option>
                            <option value="1.5">1.5×</option>
                            <option value="2">2×</option>
                        </select>

                        <!-- Download -->
                        <a 
                            href="{{ $audioUrl }}" 
                            download
                            class="p-1 hover:bg-gray-100 dark:hover:bg-gray-700 rounded transition-colors"
                            title="Download recording"
                        >
                            <x-heroicon-o-arrow-down-tray class="w-5 h-5" />
                        </a>
                    </div>
                </div>
            </div>

            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const callId = '{{ $callId }}';
                    const audio = document.getElementById(`audio-${callId}`);
                    const playBtn = document.getElementById(`play-${callId}`);
                    const playIcon = document.getElementById(`play-svg-${callId}`);
                    const pauseIcon = document.getElementById(`pause-svg-${callId}`);
                    const waveformPlayBtn = document.getElementById(`waveform-play-${callId}`);
                    const progressBar = document.getElementById(`progress-${callId}`);
                    const seekSlider = document.getElementById(`seek-${callId}`);
                    const currentTime = document.getElementById(`time-${callId}`);
                    const totalTime = document.getElementById(`total-${callId}`);
                    const callDuration = document.getElementById(`call-duration-${callId}`);
                    const skipBack = document.getElementById(`skip-back-${callId}`);
                    const skipForward = document.getElementById(`skip-forward-${callId}`);
                    const volumeSlider = document.getElementById(`volume-${callId}`);
                    const muteBtn = document.getElementById(`mute-${callId}`);
                    const volumeIcon = document.getElementById(`volume-icon-${callId}`);
                    const muteIcon = document.getElementById(`mute-icon-${callId}`);
                    const rateSelect = document.getElementById(`rate-${callId}`);
                    const canvas = document.getElementById(`waveform-${callId}`);
                    const ctx = canvas.getContext('2d');

                    // Format time helper
                    function formatTime(seconds) {
                        if (isNaN(seconds)) return '00:00';
                        const mins = Math.floor(seconds / 60);
                        const secs = Math.floor(seconds % 60);
                        return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
                    }

                    // Draw waveform
                    function drawWaveform() {
                        const width = canvas.width = canvas.offsetWidth;
                        const height = canvas.height = canvas.offsetHeight;
                        const bars = 60;
                        const barWidth = width / bars;
                        
                        ctx.clearRect(0, 0, width, height);
                        
                        for (let i = 0; i < bars; i++) {
                            const barHeight = Math.random() * height * 0.7 + height * 0.15;
                            const x = i * barWidth;
                            const y = (height - barHeight) / 2;
                            
                            // Gradient for bars
                            const gradient = ctx.createLinearGradient(0, y, 0, y + barHeight);
                            gradient.addColorStop(0, 'rgba(99, 102, 241, 0.8)');
                            gradient.addColorStop(1, 'rgba(139, 92, 246, 0.8)');
                            
                            ctx.fillStyle = gradient;
                            ctx.fillRect(x + barWidth * 0.2, y, barWidth * 0.6, barHeight);
                        }
                    }

                    drawWaveform();

                    // Play/Pause functionality
                    function togglePlayPause() {
                        if (audio.paused) {
                            audio.play();
                            playIcon.classList.add('hidden');
                            pauseIcon.classList.remove('hidden');
                        } else {
                            audio.pause();
                            playIcon.classList.remove('hidden');
                            pauseIcon.classList.add('hidden');
                        }
                    }

                    playBtn.addEventListener('click', togglePlayPause);
                    waveformPlayBtn.addEventListener('click', togglePlayPause);

                    // Update progress and time
                    audio.addEventListener('timeupdate', () => {
                        const percent = (audio.currentTime / audio.duration) * 100;
                        progressBar.style.width = `${percent}%`;
                        seekSlider.value = percent;
                        currentTime.textContent = formatTime(audio.currentTime);
                    });

                    // Metadata loaded
                    audio.addEventListener('loadedmetadata', () => {
                        totalTime.textContent = formatTime(audio.duration);
                        callDuration.textContent = formatTime(audio.duration);
                    });

                    // Seeking
                    seekSlider.addEventListener('input', () => {
                        const time = (seekSlider.value / 100) * audio.duration;
                        audio.currentTime = time;
                    });

                    // Skip controls
                    skipBack.addEventListener('click', () => {
                        audio.currentTime = Math.max(0, audio.currentTime - 15);
                    });

                    skipForward.addEventListener('click', () => {
                        audio.currentTime = Math.min(audio.duration, audio.currentTime + 15);
                    });

                    // Volume control
                    volumeSlider.addEventListener('input', () => {
                        audio.volume = volumeSlider.value / 100;
                        if (audio.volume === 0) {
                            volumeIcon.classList.add('hidden');
                            muteIcon.classList.remove('hidden');
                        } else {
                            volumeIcon.classList.remove('hidden');
                            muteIcon.classList.add('hidden');
                        }
                    });

                    // Mute toggle
                    muteBtn.addEventListener('click', () => {
                        if (audio.muted) {
                            audio.muted = false;
                            volumeIcon.classList.remove('hidden');
                            muteIcon.classList.add('hidden');
                            volumeSlider.value = audio.volume * 100;
                        } else {
                            audio.muted = true;
                            volumeIcon.classList.add('hidden');
                            muteIcon.classList.remove('hidden');
                            volumeSlider.value = 0;
                        }
                    });

                    // Playback rate
                    rateSelect.addEventListener('change', () => {
                        audio.playbackRate = parseFloat(rateSelect.value);
                    });

                    // Reset when ended
                    audio.addEventListener('ended', () => {
                        playIcon.classList.remove('hidden');
                        pauseIcon.classList.add('hidden');
                        progressBar.style.width = '0%';
                        seekSlider.value = 0;
                    });
                });
            </script>
        @else
            <div class="text-center py-12">
                <div class="inline-flex items-center justify-center w-20 h-20 bg-gray-100 dark:bg-gray-800 rounded-full mb-4">
                    <x-heroicon-o-speaker-x-mark class="w-10 h-10 text-gray-400" />
                </div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">No Recording Available</h3>
                <p class="text-gray-500 dark:text-gray-400 max-w-sm mx-auto">
                    This call does not have an associated audio recording. Recordings may be added automatically when calls are completed.
                </p>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>