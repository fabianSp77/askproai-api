@php
    $record = $getRecord();
    $audioUrl = $record->audio_url ?? $record->recording_url ?? ($record->webhook_data['recording_url'] ?? null);
    $callId = $record->id;
    $sentimentData = $record->mlPrediction?->sentence_sentiments ?? [];
    $transcriptObject = $record->transcript_object ?? [];
@endphp

<div 
    x-data="audioPlayerEnterprise(@js($audioUrl), @js($sentimentData), @js($transcriptObject))" 
    x-init="init()"
    class="w-full"
>
    {{-- Modern Audio Player UI --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        {{-- Player Header --}}
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-900">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="p-2.5 bg-primary-100 dark:bg-primary-900/30 rounded-lg">
                        <svg class="w-5 h-5 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"></path>
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Audio-Aufzeichnung</h4>
                        <p class="text-xs text-gray-500 dark:text-gray-400" x-show="!loading && audioUrl">
                            <span x-text="formatTime(currentTime)"></span> / <span x-text="formatTime(duration)"></span>
                        </p>
                    </div>
                </div>
                
                {{-- Playback Speed Control --}}
                <div class="flex items-center gap-3" x-show="audioUrl && !loading">
                    <div class="flex items-center gap-2">
                        <label for="playback-speed" class="text-xs text-gray-500 dark:text-gray-400">Geschwindigkeit:</label>
                        <select 
                            id="playback-speed"
                            x-model="playbackRate" 
                            @change="changePlaybackRate()"
                            class="text-xs px-2 py-1 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 focus:ring-primary-500 focus:border-primary-500"
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
        </div>
        
        {{-- Waveform Container --}}
        <div class="px-6 py-4">
            {{-- Loading State --}}
            <div x-show="loading" class="h-16 flex items-center justify-center">
                <div class="flex items-center gap-3">
                    <div class="animate-spin h-5 w-5 text-primary-500">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                    <span class="text-sm text-gray-600 dark:text-gray-400">Audio wird geladen...</span>
                </div>
            </div>
            
            {{-- Waveform --}}
            <div id="waveform-enterprise" class="w-full h-16 rounded-lg bg-gray-50 dark:bg-gray-900/50" x-show="!loading"></div>
            
            {{-- No Audio Message --}}
            <div x-show="!audioUrl && !loading" class="h-16 flex items-center justify-center bg-gray-50 dark:bg-gray-900/50 rounded-lg">
                <div class="text-center">
                    <svg class="w-8 h-8 mx-auto mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"></path>
                    </svg>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Keine Aufzeichnung verfügbar</p>
                </div>
            </div>
            
            {{-- Progress Bar (Alternative to waveform when loading fails) --}}
            <div x-show="!loading && audioUrl && waveformError" class="w-full h-16 flex items-center">
                <div class="w-full">
                    <div class="relative h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                        <div class="absolute h-full bg-primary-500 transition-all duration-100 ease-linear" 
                             :style="`width: ${(currentTime / duration) * 100}%`"></div>
                    </div>
                    <div class="flex justify-between mt-1 text-xs text-gray-500 dark:text-gray-400">
                        <span x-text="formatTime(currentTime)"></span>
                        <span x-text="formatTime(duration)"></span>
                    </div>
                </div>
            </div>
        </div>
        
        {{-- Controls --}}
        <div class="px-6 pb-4" x-show="audioUrl && !loading">
            <div class="flex items-center gap-4">
                {{-- Play/Pause Button --}}
                <button 
                    @click="togglePlayPause()"
                    class="group relative p-3 rounded-full bg-primary-500 hover:bg-primary-600 text-white transition-all transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 shadow-lg hover:shadow-xl"
                    :class="{ 'opacity-50': loading }"
                    :disabled="loading"
                >
                    <svg x-show="!playing" class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M6.3 2.841A1.5 1.5 0 004 4.11V15.89a1.5 1.5 0 002.3 1.269l9.344-5.89a1.5 1.5 0 000-2.538L6.3 2.84z" />
                    </svg>
                    <svg x-show="playing" class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zM7 8a1 1 0 012 0v4a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v4a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                </button>
                
                {{-- Skip Buttons --}}
                <div class="flex items-center gap-2">
                    <button 
                        @click="skipBackward()"
                        class="p-2 rounded-lg bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 transition-all"
                        title="10 Sekunden zurück"
                    >
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M8.445 14.832A1 1 0 0010 14v-2.798l5.445 3.63A1 1 0 0017 14V6a1 1 0 00-1.555-.832L10 8.798V6a1 1 0 00-1.555-.832l-6 4a1 1 0 000 1.664l6 4z" />
                        </svg>
                    </button>
                    
                    <button 
                        @click="skipForward()"
                        class="p-2 rounded-lg bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 transition-all"
                        title="10 Sekunden vor"
                    >
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M4.555 5.168A1 1 0 003 6v8a1 1 0 001.555.832L10 11.202V14a1 1 0 001.555.832l6-4a1 1 0 000-1.664l-6-4A1 1 0 0010 6v2.798l-5.445-3.63z" />
                        </svg>
                    </button>
                </div>
                
                {{-- Volume Control --}}
                <div class="flex items-center gap-2 ml-auto">
                    <button @click="toggleMute()" class="p-2 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200">
                        <svg x-show="volume > 0" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M9.383 3.076A1 1 0 0110 4v12a1 1 0 01-1.707.707L4.586 13H2a1 1 0 01-1-1V8a1 1 0 011-1h2.586l3.707-3.707a1 1 0 011.09-.217zM14.657 2.929a1 1 0 011.414 0A9.972 9.972 0 0119 10a9.972 9.972 0 01-2.929 7.071 1 1 0 01-1.414-1.414A7.971 7.971 0 0017 10c0-2.21-.894-4.208-2.343-5.657a1 1 0 010-1.414zm-2.829 2.828a1 1 0 011.415 0A5.983 5.983 0 0115 10a5.984 5.984 0 01-1.757 4.243 1 1 0 01-1.415-1.415A3.984 3.984 0 0013 10a3.983 3.983 0 00-1.172-2.828 1 1 0 010-1.415z" clip-rule="evenodd" />
                        </svg>
                        <svg x-show="volume === 0" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M9.383 3.076A1 1 0 0110 4v12a1 1 0 01-1.707.707L4.586 13H2a1 1 0 01-1-1V8a1 1 0 011-1h2.586l3.707-3.707a1 1 0 011.09-.217zM12.293 7.293a1 1 0 011.414 0L15 8.586l1.293-1.293a1 1 0 111.414 1.414L16.414 10l1.293 1.293a1 1 0 01-1.414 1.414L15 11.414l-1.293 1.293a1 1 0 01-1.414-1.414L13.586 10l-1.293-1.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>
                    <input 
                        type="range" 
                        x-model="volume" 
                        @input="changeVolume()"
                        min="0" 
                        max="100" 
                        class="w-24 h-1 bg-gray-200 dark:bg-gray-700 rounded-lg appearance-none cursor-pointer slider-thumb"
                    >
                    <span class="text-xs text-gray-500 dark:text-gray-400 w-10 text-right" x-text="volume + '%'"></span>
                </div>
            </div>
        </div>
        
        {{-- Sentiment Timeline --}}
        <div class="px-6 pb-4 border-t border-gray-100 dark:border-gray-700" x-show="!loading && sentimentData.length > 0">
            <div class="pt-3">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-medium text-gray-700 dark:text-gray-300">Sentiment-Verlauf</span>
                    <div class="flex items-center gap-4 text-xs">
                        <span class="flex items-center gap-1">
                            <span class="w-2 h-2 rounded-full bg-green-500"></span>
                            <span class="text-gray-600 dark:text-gray-400">Positiv</span>
                        </span>
                        <span class="flex items-center gap-1">
                            <span class="w-2 h-2 rounded-full bg-gray-400"></span>
                            <span class="text-gray-600 dark:text-gray-400">Neutral</span>
                        </span>
                        <span class="flex items-center gap-1">
                            <span class="w-2 h-2 rounded-full bg-red-500"></span>
                            <span class="text-gray-600 dark:text-gray-400">Negativ</span>
                        </span>
                    </div>
                </div>
                <canvas x-ref="sentimentCanvas" class="w-full h-8 rounded-md"></canvas>
            </div>
        </div>
    </div>
    
    {{-- Audio element (hidden) --}}
    <audio x-ref="audio" :src="audioUrl" preload="metadata" class="hidden" crossorigin="anonymous"></audio>
</div>

@push('scripts')
<script src="https://unpkg.com/wavesurfer.js@7.7.3/dist/wavesurfer.min.js"></script>

<script>
function audioPlayerEnterprise(audioUrl, sentimentData, transcriptObject) {
    return {
        audioUrl: audioUrl,
        sentimentData: sentimentData || [],
        transcriptObject: transcriptObject || [],
        wavesurfer: null,
        playing: false,
        loading: true,
        currentTime: 0,
        duration: 0,
        playbackRate: 1,
        volume: 80,
        previousVolume: 80,
        waveformError: false,
        
        init() {
            if (!this.audioUrl) {
                this.loading = false;
                return;
            }
            
            // Try to initialize WaveSurfer with error handling
            this.initializeWaveSurfer();
        },
        
        initializeWaveSurfer() {
            try {
                // Create mono waveform with enterprise styling
                this.wavesurfer = WaveSurfer.create({
                    container: '#waveform-enterprise',
                    waveColor: 'rgba(156, 163, 175, 0.8)', // gray-400 with opacity
                    progressColor: '#3b82f6', // primary-500
                    cursorColor: '#1e40af', // primary-800
                    barWidth: 3,
                    barRadius: 3,
                    barGap: 2,
                    barMinHeight: 1,
                    responsive: true,
                    height: 64,
                    normalize: true,
                    backend: 'MediaElement',
                    mediaControls: false,
                    interact: true,
                    dragToSeek: true,
                    splitChannels: false, // IMPORTANT: Force mono display
                    hideScrollbar: true,
                    fillParent: true,
                    plugins: [], // No plugins to ensure mono
                    audioRate: 1,
                    autoCenter: true,
                    partialRender: true
                });
                
                // Set initial volume
                this.wavesurfer.setVolume(this.volume / 100);
                
                // Load audio with CORS handling
                this.wavesurfer.load(this.audioUrl);
                
                // Event listeners
                this.wavesurfer.on('ready', () => {
                    this.loading = false;
                    this.duration = this.wavesurfer.getDuration();
                    this.drawSentimentTimeline();
                });
                
                this.wavesurfer.on('audioprocess', () => {
                    this.currentTime = this.wavesurfer.getCurrentTime();
                    this.updateSentimentIndicator();
                });
                
                this.wavesurfer.on('play', () => {
                    this.playing = true;
                });
                
                this.wavesurfer.on('pause', () => {
                    this.playing = false;
                });
                
                this.wavesurfer.on('finish', () => {
                    this.playing = false;
                    this.currentTime = this.duration;
                });
                
                this.wavesurfer.on('error', (e) => {
                    console.warn('WaveSurfer error:', e);
                    this.waveformError = true;
                    this.loading = false;
                    // Fallback to basic audio controls
                    this.initBasicAudio();
                });
                
                // Listen for external events
                window.addEventListener('sentence-selected', (event) => {
                    if (event.detail && event.detail.startTime) {
                        this.seekTo(parseFloat(event.detail.startTime));
                        if (!this.playing) {
                            this.togglePlayPause();
                        }
                    }
                });
            } catch (error) {
                console.warn('Failed to initialize WaveSurfer:', error);
                this.waveformError = true;
                this.loading = false;
                this.initBasicAudio();
            }
        },
        
        initBasicAudio() {
            // Fallback to basic HTML5 audio
            const audio = this.$refs.audio;
            if (!audio) return;
            
            audio.addEventListener('loadedmetadata', () => {
                this.duration = audio.duration;
                this.loading = false;
            });
            
            audio.addEventListener('timeupdate', () => {
                this.currentTime = audio.currentTime;
            });
            
            audio.addEventListener('play', () => {
                this.playing = true;
            });
            
            audio.addEventListener('pause', () => {
                this.playing = false;
            });
            
            audio.addEventListener('ended', () => {
                this.playing = false;
            });
            
            audio.volume = this.volume / 100;
            audio.playbackRate = this.playbackRate;
        },
        
        togglePlayPause() {
            if (this.wavesurfer && !this.waveformError) {
                this.wavesurfer.playPause();
            } else if (this.$refs.audio) {
                if (this.playing) {
                    this.$refs.audio.pause();
                } else {
                    this.$refs.audio.play();
                }
            }
        },
        
        skipBackward() {
            if (this.wavesurfer && !this.waveformError) {
                this.wavesurfer.skip(-10);
            } else if (this.$refs.audio) {
                this.$refs.audio.currentTime = Math.max(0, this.$refs.audio.currentTime - 10);
            }
        },
        
        skipForward() {
            if (this.wavesurfer && !this.waveformError) {
                this.wavesurfer.skip(10);
            } else if (this.$refs.audio) {
                this.$refs.audio.currentTime = Math.min(this.duration, this.$refs.audio.currentTime + 10);
            }
        },
        
        changePlaybackRate() {
            const rate = parseFloat(this.playbackRate);
            if (this.wavesurfer && !this.waveformError) {
                this.wavesurfer.setPlaybackRate(rate);
            } else if (this.$refs.audio) {
                this.$refs.audio.playbackRate = rate;
            }
        },
        
        changeVolume() {
            const vol = this.volume / 100;
            if (this.wavesurfer && !this.waveformError) {
                this.wavesurfer.setVolume(vol);
            } else if (this.$refs.audio) {
                this.$refs.audio.volume = vol;
            }
        },
        
        toggleMute() {
            if (this.volume > 0) {
                this.previousVolume = this.volume;
                this.volume = 0;
            } else {
                this.volume = this.previousVolume || 80;
            }
            this.changeVolume();
        },
        
        seekTo(time) {
            if (this.wavesurfer && !this.waveformError && time >= 0 && time <= this.duration) {
                this.wavesurfer.seekTo(time / this.duration);
            } else if (this.$refs.audio) {
                this.$refs.audio.currentTime = time;
            }
        },
        
        formatTime(seconds) {
            if (!seconds || isNaN(seconds)) return '0:00';
            const minutes = Math.floor(seconds / 60);
            const secs = Math.floor(seconds % 60);
            return `${minutes}:${secs.toString().padStart(2, '0')}`;
        },
        
        drawSentimentTimeline() {
            const canvas = this.$refs.sentimentCanvas;
            if (!canvas || this.sentimentData.length === 0) return;
            
            const ctx = canvas.getContext('2d');
            const width = canvas.offsetWidth;
            const height = canvas.offsetHeight;
            
            // Set canvas size
            canvas.width = width;
            canvas.height = height;
            
            // Clear canvas
            ctx.clearRect(0, 0, width, height);
            
            // Draw background
            ctx.fillStyle = 'rgba(229, 231, 235, 0.3)'; // gray-200 with opacity
            ctx.fillRect(0, 0, width, height);
            
            // Draw sentiment blocks with smooth transitions
            this.sentimentData.forEach((segment, index) => {
                const startX = (segment.start_time / this.duration) * width;
                const endX = (segment.end_time / this.duration) * width;
                const blockWidth = endX - startX;
                
                // Create gradient for smoother transitions
                const gradient = ctx.createLinearGradient(startX, 0, endX, 0);
                
                // Set color based on sentiment with gradients
                switch(segment.sentiment) {
                    case 'positive':
                        gradient.addColorStop(0, 'rgba(34, 197, 94, 0.8)'); // green-500
                        gradient.addColorStop(1, 'rgba(34, 197, 94, 0.6)');
                        break;
                    case 'negative':
                        gradient.addColorStop(0, 'rgba(239, 68, 68, 0.8)'); // red-500
                        gradient.addColorStop(1, 'rgba(239, 68, 68, 0.6)');
                        break;
                    default:
                        gradient.addColorStop(0, 'rgba(156, 163, 175, 0.8)'); // gray-400
                        gradient.addColorStop(1, 'rgba(156, 163, 175, 0.6)');
                }
                
                ctx.fillStyle = gradient;
                ctx.fillRect(startX, 0, blockWidth, height);
            });
            
            // Save context for position indicator
            this.sentimentCanvasContext = ctx;
            this.sentimentCanvasWidth = width;
            this.sentimentCanvasHeight = height;
        },
        
        updateSentimentIndicator() {
            if (!this.sentimentCanvasContext || !this.duration) return;
            
            // Redraw sentiment timeline
            this.drawSentimentTimeline();
            
            // Draw current position indicator
            const ctx = this.sentimentCanvasContext;
            const x = (this.currentTime / this.duration) * this.sentimentCanvasWidth;
            
            // Draw position line with glow effect
            ctx.shadowColor = '#1e40af';
            ctx.shadowBlur = 4;
            ctx.strokeStyle = '#1e40af'; // primary-800
            ctx.lineWidth = 2;
            ctx.beginPath();
            ctx.moveTo(x, 0);
            ctx.lineTo(x, this.sentimentCanvasHeight);
            ctx.stroke();
            ctx.shadowBlur = 0;
            
            // Emit current sentiment
            const currentSegment = this.sentimentData.find(segment => 
                this.currentTime >= (segment.start_time || 0) && 
                this.currentTime <= (segment.end_time || this.duration)
            );
            
            if (currentSegment) {
                window.dispatchEvent(new CustomEvent('current-sentiment', {
                    detail: { 
                        sentiment: currentSegment.sentiment,
                        score: currentSegment.score 
                    }
                }));
            }
        },
        
        destroy() {
            if (this.wavesurfer) {
                this.wavesurfer.destroy();
            }
        }
    }
}
</script>
@endpush

<style>
/* Enterprise Audio Player Styles */
#waveform-enterprise {
    background: linear-gradient(to bottom, rgba(243, 244, 246, 0.5), rgba(249, 250, 251, 0.5));
    border-radius: 0.5rem;
    position: relative;
    overflow: hidden;
}

.dark #waveform-enterprise {
    background: linear-gradient(to bottom, rgba(31, 41, 55, 0.5), rgba(17, 24, 39, 0.5));
}

/* Ensure single waveform */
#waveform-enterprise wave {
    height: 64px !important;
    overflow: hidden !important;
}

#waveform-enterprise canvas {
    height: 64px !important;
    max-height: 64px !important;
}

/* Custom volume slider styling */
.slider-thumb {
    -webkit-appearance: none;
    appearance: none;
}

.slider-thumb::-webkit-slider-track {
    height: 4px;
    background: rgba(156, 163, 175, 0.3);
    border-radius: 2px;
}

.slider-thumb::-webkit-slider-thumb {
    -webkit-appearance: none;
    width: 14px;
    height: 14px;
    background: #3b82f6;
    border-radius: 50%;
    cursor: pointer;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
    transition: all 0.2s;
}

.slider-thumb::-webkit-slider-thumb:hover {
    background: #2563eb;
    transform: scale(1.1);
}

.slider-thumb::-moz-range-track {
    height: 4px;
    background: rgba(156, 163, 175, 0.3);
    border-radius: 2px;
}

.slider-thumb::-moz-range-thumb {
    width: 14px;
    height: 14px;
    background: #3b82f6;
    border-radius: 50%;
    cursor: pointer;
    border: none;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
    transition: all 0.2s;
}

.slider-thumb::-moz-range-thumb:hover {
    background: #2563eb;
    transform: scale(1.1);
}

/* Loading animation */
.animate-spin {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

/* Smooth transitions */
#waveform-enterprise,
.slider-thumb,
button {
    transition: all 0.2s ease-in-out;
}

/* Focus states */
button:focus {
    outline: none;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.5);
}

select:focus {
    outline: none;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.5);
}
</style>