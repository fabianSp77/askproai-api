@php
    $record = $getRecord();
    $audioUrl = $record->audio_url ?? $record->recording_url ?? ($record->webhook_data['recording_url'] ?? null);
    $callId = $record->id;
    $sentimentData = $record->mlPrediction?->sentence_sentiments ?? [];
    $transcriptObject = $record->transcript_object ?? [];
@endphp

<div 
    x-data="audioPlayerSentiment(@js($audioUrl), @js($sentimentData), @js($transcriptObject))" 
    x-init="init()"
    class="w-full bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6"
>
    {{-- Audio Player Header --}}
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
            Anruf-Aufzeichnung
        </h3>
        <div class="text-sm text-gray-500 dark:text-gray-400" x-show="!loading && audioUrl">
            <span x-text="formatTime(currentTime)"></span> / <span x-text="formatTime(duration)"></span>
        </div>
    </div>
    
    {{-- Audio Controls --}}
    <div class="flex items-center gap-4 mb-6" x-show="audioUrl && !loading">
        <button 
            @click="togglePlayPause()"
            class="p-3 rounded-full bg-primary-500 hover:bg-primary-600 text-white transition-colors"
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
        
        <button 
            @click="skipBackward()"
            class="p-2 rounded-full bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 transition-colors"
        >
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                <path d="M8.445 14.832A1 1 0 0010 14v-2.798l5.445 3.63A1 1 0 0017 14V6a1 1 0 00-1.555-.832L10 8.798V6a1 1 0 00-1.555-.832l-6 4a1 1 0 000 1.664l6 4z" />
            </svg>
        </button>
        
        <button 
            @click="skipForward()"
            class="p-2 rounded-full bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 transition-colors"
        >
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                <path d="M4.555 5.168A1 1 0 003 6v8a1 1 0 001.555.832L10 11.202V14a1 1 0 001.555.832l6-4a1 1 0 000-1.664l-6-4A1 1 0 0010 6v2.798l-5.445-3.63z" />
            </svg>
        </button>
        
        <div class="flex items-center gap-2 ml-4">
            <span class="text-sm text-gray-500 dark:text-gray-400">Speed:</span>
            <select 
                x-model="playbackRate" 
                @change="changePlaybackRate()"
                class="text-sm rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700"
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
    
    {{-- Waveform Container with Sentiment Overlay --}}
    <div class="relative">
        {{-- Loading State --}}
        <div x-show="loading" class="absolute inset-0 flex items-center justify-center bg-gray-100 dark:bg-gray-800 rounded-lg">
            <div class="flex items-center gap-2">
                <svg class="animate-spin h-5 w-5 text-primary-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="text-sm text-gray-600 dark:text-gray-400">Audio wird geladen...</span>
            </div>
        </div>
        
        {{-- Waveform --}}
        <div id="waveform" class="w-full h-20" x-show="!loading"></div>
        
        {{-- Sentiment Timeline --}}
        <div id="sentiment-timeline" class="w-full h-6 mt-3" x-show="!loading && sentimentData.length > 0">
            <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Sentiment-Verlauf:</div>
            <canvas x-ref="sentimentCanvas" class="w-full h-4 rounded"></canvas>
        </div>
    </div>
    
    {{-- Sentiment Legend --}}
    <div class="flex items-center justify-center gap-6 mt-4 text-sm">
        <div class="flex items-center gap-2">
            <div class="w-4 h-4 bg-green-500 rounded"></div>
            <span class="text-gray-600 dark:text-gray-400">Positiv</span>
        </div>
        <div class="flex items-center gap-2">
            <div class="w-4 h-4 bg-gray-400 rounded"></div>
            <span class="text-gray-600 dark:text-gray-400">Neutral</span>
        </div>
        <div class="flex items-center gap-2">
            <div class="w-4 h-4 bg-red-500 rounded"></div>
            <span class="text-gray-600 dark:text-gray-400">Negativ</span>
        </div>
    </div>
    
    {{-- Audio element (hidden) --}}
    <audio x-ref="audio" :src="audioUrl" preload="metadata"></audio>
</div>

@push('scripts')
<script src="https://unpkg.com/wavesurfer.js@7.7.3/dist/wavesurfer.min.js"></script>

<script>
function audioPlayerSentiment(audioUrl, sentimentData, transcriptObject) {
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
        
        init() {
            if (!this.audioUrl) {
                this.loading = false;
                this.showFallbackMessage();
                return;
            }
            
            // Check if audio URL is accessible
            this.checkAudioUrl();
        },
        
        checkAudioUrl() {
            // First try to load the audio to check if it's accessible
            const audio = new Audio();
            audio.crossOrigin = 'anonymous';
            
            audio.onerror = () => {
                console.warn('Audio URL not accessible, showing fallback message');
                this.loading = false;
                this.showFallbackMessage();
            };
            
            audio.onloadedmetadata = () => {
                // Audio is accessible, initialize WaveSurfer
                this.initializeWaveSurfer();
            };
            
            audio.src = this.audioUrl;
        },
        
        showFallbackMessage() {
            const container = document.getElementById('waveform');
            if (container) {
                container.innerHTML = `
                    <div class="flex items-center justify-center h-full text-gray-500">
                        <div class="text-center">
                            <svg class="w-12 h-12 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"></path>
                            </svg>
                            <p class="text-sm font-medium">Keine Aufzeichnung vorhanden</p>
                            <p class="text-xs mt-1 text-gray-400">Für diesen Anruf wurde keine Audio-Aufzeichnung gespeichert</p>
                        </div>
                    </div>
                `;
            }
        },
        
        initializeWaveSurfer() {
            // Initialize WaveSurfer with mono output
            this.wavesurfer = WaveSurfer.create({
                container: '#waveform',
                waveColor: 'rgba(59, 130, 246, 0.5)',
                progressColor: '#3b82f6',
                cursorColor: '#1e40af',
                barWidth: 3,
                barRadius: 4,
                responsive: true,
                height: 64,  // Single waveform height
                normalize: true,
                backend: 'MediaElement',
                mediaControls: false,
                interact: true,
                dragToSeek: true,
                splitChannels: false,  // Force mono display
                barGap: 2,
                barMinHeight: 1,
                cursorWidth: 2,
                hideScrollbar: true,
                plugins: []  // Ensure no plugins that might split channels
            });
            
            // Load audio
            this.wavesurfer.load(this.audioUrl);
            
            // Event listeners
            this.wavesurfer.on('ready', () => {
                this.loading = false;
                this.duration = this.wavesurfer.getDuration();
                this.drawSentimentTimeline();
                // Regions plugin not available in this version
                // this.addSentimentRegions();
            });
            
            this.wavesurfer.on('audioprocess', () => {
                this.currentTime = this.wavesurfer.getCurrentTime();
                this.highlightCurrentSentiment();
            });
            
            this.wavesurfer.on('play', () => {
                this.playing = true;
            });
            
            this.wavesurfer.on('pause', () => {
                this.playing = false;
            });
            
            this.wavesurfer.on('error', (e) => {
                console.error('WaveSurfer error:', e);
                this.loading = false;
                this.$dispatch('audio-error', { error: e });
            });
            
            // Listen for sentence selection events
            window.addEventListener('sentence-selected', (event) => {
                if (event.detail && event.detail.startTime) {
                    this.seekTo(parseFloat(event.detail.startTime));
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
                
                // Add slight border
                ctx.strokeStyle = 'rgba(0, 0, 0, 0.1)';
                ctx.strokeRect(startX, 0, blockWidth, height);
            });
        },
        
        // Regions functionality removed - using visual timeline instead
        /* addSentimentRegions() {
            if (!this.wavesurfer || this.sentimentData.length === 0) return;
            
            // Create regions for sentiment segments
            this.sentimentData.forEach((segment, index) => {
                let color;
                switch(segment.sentiment) {
                    case 'positive':
                        color = 'rgba(16, 185, 129, 0.1)'; // green with transparency
                        break;
                    case 'negative':
                        color = 'rgba(239, 68, 68, 0.1)'; // red with transparency
                        break;
                    default:
                        color = 'rgba(156, 163, 175, 0.1)'; // gray with transparency
                }
                
                // Add region to waveform
                this.wavesurfer.addRegion({
                    start: segment.start_time || 0,
                    end: segment.end_time || this.duration,
                    color: color,
                    drag: false,
                    resize: false,
                    id: `sentiment-${index}`
                });
            });
        }, */
        
        highlightCurrentSentiment() {
            // Find current sentiment segment
            const currentSegment = this.sentimentData.find(segment => 
                this.currentTime >= (segment.start_time || 0) && 
                this.currentTime <= (segment.end_time || this.duration)
            );
            
            if (currentSegment) {
                this.$dispatch('current-sentiment', { 
                    sentiment: currentSegment.sentiment,
                    score: currentSegment.score 
                });
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
#waveform {
    border-radius: 0.5rem;
    background: rgba(0, 0, 0, 0.05);
}

.dark #waveform {
    background: rgba(255, 255, 255, 0.05);
}

#sentiment-timeline canvas {
    border-radius: 0.25rem;
}
</style>