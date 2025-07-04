@php
    $record = $getRecord();
    $audioUrl = $record->audio_url ?? $record->recording_url ?? ($record->webhook_data['recording_url'] ?? null);
    $callId = $record->id;
    $sentimentData = $record->mlPrediction?->sentence_sentiments ?? [];
    $transcriptObject = $record->transcript_object ?? [];
@endphp

<div 
    x-data="audioPlayerSimple(@js($audioUrl), @js($sentimentData), @js($transcriptObject))" 
    x-init="init()"
    class="w-full relative overflow-hidden"
>
    {{-- Modern Glassmorphism Background --}}
    <div class="absolute inset-0 bg-gradient-to-br from-blue-50/50 to-purple-50/50 dark:from-blue-900/20 dark:to-purple-900/20"></div>
    <div class="relative bg-white/80 dark:bg-gray-800/80 backdrop-blur-xl rounded-2xl shadow-2xl border border-gray-200/50 dark:border-gray-700/50 p-8">
    {{-- Audio Player Header --}}
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            <div class="p-2 bg-gradient-to-br from-blue-500 to-purple-600 rounded-lg text-white">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M18 3a1 1 0 00-1.196-.98l-10 2A1 1 0 006 5v9.114A4.369 4.369 0 005 14c-1.657 0-3 .895-3 2s1.343 2 3 2 3-.895 3-2V7.82l8-1.6v5.894A4.37 4.37 0 0015 12c-1.657 0-3 .895-3 2s1.343 2 3 2 3-.895 3-2V3z" />
                </svg>
            </div>
            <h3 class="text-xl font-semibold bg-gradient-to-r from-gray-900 to-gray-700 dark:from-gray-100 dark:to-gray-300 bg-clip-text text-transparent">
                Anruf-Aufzeichnung
            </h3>
        </div>
        <div class="flex items-center gap-2 text-sm" x-show="!loading && audioUrl">
            <div class="px-3 py-1 bg-gray-100 dark:bg-gray-700 rounded-full text-gray-600 dark:text-gray-300">
                <span x-text="formatTime(currentTime)"></span> / <span x-text="formatTime(duration)"></span>
            </div>
        </div>
    </div>
    
    {{-- Audio Controls --}}
    <div class="flex items-center justify-between gap-4 mb-8" x-show="audioUrl && !loading">
        <div class="flex items-center gap-3">
            {{-- Play/Pause Button --}}
            <button 
                @click="togglePlayPause()"
                class="group relative p-4 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 hover:from-blue-600 hover:to-purple-700 text-white shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-200"
                :class="{ 'opacity-50': loading }"
                :disabled="loading"
            >
                <div class="absolute inset-0 rounded-full bg-white opacity-0 group-hover:opacity-20 transition-opacity"></div>
                <svg x-show="!playing" class="w-6 h-6 relative z-10" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M6.3 2.841A1.5 1.5 0 004 4.11V15.89a1.5 1.5 0 002.3 1.269l9.344-5.89a1.5 1.5 0 000-2.538L6.3 2.84z" />
                </svg>
                <svg x-show="playing" class="w-6 h-6 relative z-10" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zM7 8a1 1 0 012 0v4a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v4a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
            </button>
            
            {{-- Skip Controls --}}
            <div class="flex items-center gap-1 bg-gray-100 dark:bg-gray-700/50 rounded-full p-1">
                <button 
                    @click="skipBackward()"
                    class="p-2 rounded-full hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 transition-all duration-200"
                    title="10 Sekunden zurück"
                >
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M8.445 14.832A1 1 0 0010 14v-2.798l5.445 3.63A1 1 0 0017 14V6a1 1 0 00-1.555-.832L10 8.798V6a1 1 0 00-1.555-.832l-6 4a1 1 0 000 1.664l6 4z" />
                    </svg>
                </button>
                
                <div class="px-2 text-xs font-medium text-gray-600 dark:text-gray-400">10s</div>
                
                <button 
                    @click="skipForward()"
                    class="p-2 rounded-full hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 transition-all duration-200"
                    title="10 Sekunden vor"
                >
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M4.555 5.168A1 1 0 003 6v8a1 1 0 001.555.832L10 11.202V14a1 1 0 001.555.832l6-4a1 1 0 000-1.664l-6-4A1 1 0 0010 6v2.798l-5.445-3.63z" />
                    </svg>
                </button>
            </div>
        </div>
        
        {{-- Speed Control --}}
        <div class="flex items-center gap-3">
            <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Geschwindigkeit:</label>
            <div class="flex gap-1 bg-gray-100 dark:bg-gray-700/50 rounded-lg p-1">
                <button 
                    @click="playbackRate = 0.5; changePlaybackRate()"
                    :class="playbackRate == 0.5 ? 'bg-white dark:bg-gray-600 shadow-sm' : 'hover:bg-gray-200 dark:hover:bg-gray-600'"
                    class="px-3 py-1 rounded text-sm font-medium transition-all duration-200"
                >0.5x</button>
                <button 
                    @click="playbackRate = 1; changePlaybackRate()"
                    :class="playbackRate == 1 ? 'bg-white dark:bg-gray-600 shadow-sm' : 'hover:bg-gray-200 dark:hover:bg-gray-600'"
                    class="px-3 py-1 rounded text-sm font-medium transition-all duration-200"
                >1x</button>
                <button 
                    @click="playbackRate = 1.5; changePlaybackRate()"
                    :class="playbackRate == 1.5 ? 'bg-white dark:bg-gray-600 shadow-sm' : 'hover:bg-gray-200 dark:hover:bg-gray-600'"
                    class="px-3 py-1 rounded text-sm font-medium transition-all duration-200"
                >1.5x</button>
                <button 
                    @click="playbackRate = 2; changePlaybackRate()"
                    :class="playbackRate == 2 ? 'bg-white dark:bg-gray-600 shadow-sm' : 'hover:bg-gray-200 dark:hover:bg-gray-600'"
                    class="px-3 py-1 rounded text-sm font-medium transition-all duration-200"
                >2x</button>
            </div>
        </div>
    </div>
    
    {{-- Waveform Container --}}
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
        <div :id="'waveform-' + Math.random().toString(36).substr(2, 9)" x-ref="waveformContainer" 
             class="w-full h-32 bg-gray-50 dark:bg-gray-900/50 rounded-xl" 
             x-show="!loading"></div>
        
        {{-- Sentiment Timeline --}}
        <div class="w-full h-8 mt-2" x-show="!loading && sentimentData.length > 0">
            <canvas x-ref="sentimentCanvas" class="w-full h-full"></canvas>
        </div>
    </div>
    
    {{-- Sentiment Legend --}}
    <div class="flex items-center justify-center gap-6 mt-4 text-sm" x-show="sentimentData.length > 0">
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
</div>

@pushOnce('scripts')
<script src="https://unpkg.com/wavesurfer.js@7.7.3/dist/wavesurfer.min.js"></script>
@endPushOnce

@push('scripts')
<script>
function audioPlayerSimple(audioUrl, sentimentData, transcriptObject) {
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
        instanceId: 'player-' + Math.random().toString(36).substr(2, 9),
        
        init() {
            if (!this.audioUrl) {
                this.loading = false;
                this.showFallbackMessage();
                return;
            }
            
            // Cleanup any existing instance
            if (this.wavesurfer) {
                this.wavesurfer.destroy();
                this.wavesurfer = null;
            }
            
            // Check if audio URL is accessible
            this.checkAudioUrl();
            
            // Cleanup on Alpine destroy
            this.$cleanup = () => {
                if (this.wavesurfer) {
                    this.wavesurfer.destroy();
                    this.wavesurfer = null;
                }
            };
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
            const container = this.$refs.waveformContainer;
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
            // Initialize WaveSurfer without regions plugin
            this.wavesurfer = WaveSurfer.create({
                container: this.$refs.waveformContainer,
                waveColor: '#94a3b8',
                progressColor: '#3b82f6',
                cursorColor: '#1e40af',
                barWidth: 2,
                barRadius: 3,
                responsive: true,
                height: 128,
                normalize: true,
                backend: 'MediaElement',
                mediaControls: false,
                interact: true,
                dragToSeek: true
            });
            
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
                this.updateSentimentProgress();
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
        
        updateSentimentProgress() {
            // Update progress indicator on sentiment timeline
            const canvas = this.$refs.sentimentCanvas;
            if (!canvas || this.sentimentData.length === 0) return;
            
            const ctx = canvas.getContext('2d');
            const width = canvas.width;
            const height = canvas.height;
            
            // Redraw timeline
            this.drawSentimentTimeline();
            
            // Draw progress line
            const progressX = (this.currentTime / this.duration) * width;
            ctx.strokeStyle = '#1e40af';
            ctx.lineWidth = 2;
            ctx.beginPath();
            ctx.moveTo(progressX, 0);
            ctx.lineTo(progressX, height);
            ctx.stroke();
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
#waveform-simple {
    border-radius: 0.5rem;
    background: rgba(0, 0, 0, 0.05);
}

.dark #waveform-simple {
    background: rgba(255, 255, 255, 0.05);
}

canvas {
    border-radius: 0.25rem;
}
</style>