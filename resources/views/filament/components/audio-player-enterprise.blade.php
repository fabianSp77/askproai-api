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
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        {{-- Player Header --}}
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-primary-50 dark:bg-primary-900/20 rounded-lg">
                        <svg class="w-5 h-5 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"></path>
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100">Audio-Aufzeichnung</h4>
                        <p class="text-xs text-gray-500 dark:text-gray-400" x-show="!loading && audioUrl">
                            <span x-text="formatTime(currentTime)"></span> / <span x-text="formatTime(duration)"></span>
                        </p>
                    </div>
                </div>
                
                {{-- Playback Speed Control --}}
                <div class="flex items-center gap-2" x-show="audioUrl && !loading">
                    <span class="text-xs text-gray-500 dark:text-gray-400">Geschwindigkeit:</span>
                    <select 
                        x-model="playbackRate" 
                        @change="changePlaybackRate()"
                        class="text-xs px-2 py-1 rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 focus:ring-primary-500 focus:border-primary-500"
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
        
        {{-- Waveform Container --}}
        <div class="px-6 py-4">
            {{-- Loading State --}}
            <div x-show="loading" class="h-16 flex items-center justify-center">
                <div class="flex items-center gap-2">
                    <svg class="animate-spin h-5 w-5 text-primary-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span class="text-sm text-gray-600 dark:text-gray-400">Audio wird geladen...</span>
                </div>
            </div>
            
            {{-- Waveform --}}
            <div id="waveform-enterprise" class="w-full h-16 rounded" x-show="!loading"></div>
            
            {{-- No Audio Message --}}
            <div x-show="!audioUrl && !loading" class="h-16 flex items-center justify-center">
                <div class="text-center">
                    <svg class="w-8 h-8 mx-auto mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"></path>
                    </svg>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Keine Aufzeichnung verfügbar</p>
                </div>
            </div>
        </div>
        
        {{-- Controls --}}
        <div class="px-6 pb-4" x-show="audioUrl && !loading">
            <div class="flex items-center gap-4">
                {{-- Play/Pause Button --}}
                <button 
                    @click="togglePlayPause()"
                    class="p-3 rounded-full bg-primary-500 hover:bg-primary-600 text-white transition-all transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
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
                <button 
                    @click="skipBackward()"
                    class="p-2 rounded-lg bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 transition-colors"
                    title="10 Sekunden zurück"
                >
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M8.445 14.832A1 1 0 0010 14v-2.798l5.445 3.63A1 1 0 0017 14V6a1 1 0 00-1.555-.832L10 8.798V6a1 1 0 00-1.555-.832l-6 4a1 1 0 000 1.664l6 4z" />
                    </svg>
                </button>
                
                <button 
                    @click="skipForward()"
                    class="p-2 rounded-lg bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 transition-colors"
                    title="10 Sekunden vor"
                >
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M4.555 5.168A1 1 0 003 6v8a1 1 0 001.555.832L10 11.202V14a1 1 0 001.555.832l6-4a1 1 0 000-1.664l-6-4A1 1 0 0010 6v2.798l-5.445-3.63z" />
                    </svg>
                </button>
                
                {{-- Volume Control --}}
                <div class="flex items-center gap-2 ml-auto">
                    <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M9.383 3.076A1 1 0 0110 4v12a1 1 0 01-1.707.707L4.586 13H2a1 1 0 01-1-1V8a1 1 0 011-1h2.586l3.707-3.707a1 1 0 011.09-.217zM14.657 2.929a1 1 0 011.414 0A9.972 9.972 0 0119 10a9.972 9.972 0 01-2.929 7.071 1 1 0 01-1.414-1.414A7.971 7.971 0 0017 10c0-2.21-.894-4.208-2.343-5.657a1 1 0 010-1.414zm-2.829 2.828a1 1 0 011.415 0A5.983 5.983 0 0115 10a5.984 5.984 0 01-1.757 4.243 1 1 0 01-1.415-1.415A3.984 3.984 0 0013 10a3.983 3.983 0 00-1.172-2.828 1 1 0 010-1.415z" clip-rule="evenodd" />
                    </svg>
                    <input 
                        type="range" 
                        x-model="volume" 
                        @input="changeVolume()"
                        min="0" 
                        max="100" 
                        class="w-24 h-1 bg-gray-200 dark:bg-gray-700 rounded-lg appearance-none cursor-pointer"
                    >
                    <span class="text-xs text-gray-500 dark:text-gray-400 w-8" x-text="volume + '%'"></span>
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
                <canvas x-ref="sentimentCanvas" class="w-full h-8 rounded"></canvas>
            </div>
        </div>
    </div>
    
    {{-- Audio element (hidden) --}}
    <audio x-ref="audio" :src="audioUrl" preload="metadata" class="hidden"></audio>
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
        
        init() {
            if (!this.audioUrl) {
                this.loading = false;
                return;
            }
            
            this.initializeWaveSurfer();
        },
        
        initializeWaveSurfer() {
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
                plugins: [] // No plugins to ensure mono
            });
            
            // Set initial volume
            this.wavesurfer.setVolume(this.volume / 100);
            
            // Load audio
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
            });
            
            this.wavesurfer.on('error', (e) => {
                console.error('WaveSurfer error:', e);
                this.loading = false;
                this.audioUrl = null;
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
        },
        
        togglePlayPause() {
            if (this.wavesurfer) {
                this.wavesurfer.playPause();
            }
        },
        
        skipBackward() {
            if (this.wavesurfer) {
                this.wavesurfer.skip(-10);
            }
        },
        
        skipForward() {
            if (this.wavesurfer) {
                this.wavesurfer.skip(10);
            }
        },
        
        changePlaybackRate() {
            if (this.wavesurfer) {
                this.wavesurfer.setPlaybackRate(parseFloat(this.playbackRate));
            }
        },
        
        changeVolume() {
            if (this.wavesurfer) {
                this.wavesurfer.setVolume(this.volume / 100);
            }
        },
        
        seekTo(time) {
            if (this.wavesurfer && time >= 0 && time <= this.duration) {
                this.wavesurfer.seekTo(time / this.duration);
            }
        },
        
        formatTime(seconds) {
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
            
            // Draw sentiment blocks
            this.sentimentData.forEach(segment => {
                const startX = (segment.start_time / this.duration) * width;
                const endX = (segment.end_time / this.duration) * width;
                const blockWidth = endX - startX;
                
                // Set color based on sentiment
                let color;
                switch(segment.sentiment) {
                    case 'positive':
                        color = '#10b981'; // green-500
                        break;
                    case 'negative':
                        color = '#ef4444'; // red-500
                        break;
                    default:
                        color = '#9ca3af'; // gray-400
                }
                
                ctx.fillStyle = color;
                ctx.fillRect(startX, 0, blockWidth, height);
            });
            
            // Draw current position indicator
            this.sentimentCanvasContext = ctx;
            this.sentimentCanvasWidth = width;
            this.sentimentCanvasHeight = height;
        },
        
        updateSentimentIndicator() {
            if (!this.sentimentCanvasContext) return;
            
            // Redraw sentiment timeline
            this.drawSentimentTimeline();
            
            // Draw current position
            const ctx = this.sentimentCanvasContext;
            const x = (this.currentTime / this.duration) * this.sentimentCanvasWidth;
            
            ctx.strokeStyle = '#1e40af'; // primary-800
            ctx.lineWidth = 2;
            ctx.beginPath();
            ctx.moveTo(x, 0);
            ctx.lineTo(x, this.sentimentCanvasHeight);
            ctx.stroke();
            
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
    border-radius: 0.375rem;
}

.dark #waveform-enterprise {
    background: linear-gradient(to bottom, rgba(31, 41, 55, 0.5), rgba(17, 24, 39, 0.5));
}

/* Custom volume slider styling */
input[type="range"] {
    -webkit-appearance: none;
}

input[type="range"]::-webkit-slider-track {
    height: 4px;
    background: rgba(156, 163, 175, 0.3);
    border-radius: 2px;
}

input[type="range"]::-webkit-slider-thumb {
    -webkit-appearance: none;
    width: 12px;
    height: 12px;
    background: #3b82f6;
    border-radius: 50%;
    cursor: pointer;
}

input[type="range"]::-moz-range-track {
    height: 4px;
    background: rgba(156, 163, 175, 0.3);
    border-radius: 2px;
}

input[type="range"]::-moz-range-thumb {
    width: 12px;
    height: 12px;
    background: #3b82f6;
    border-radius: 50%;
    cursor: pointer;
    border: none;
}
</style>