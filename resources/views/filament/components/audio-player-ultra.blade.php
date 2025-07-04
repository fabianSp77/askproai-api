@php
    $record = $getRecord();
    $audioUrl = $record->audio_url ?? $record->recording_url ?? ($record->webhook_data['recording_url'] ?? null);
    $callId = $record->id;
    $sentimentData = $record->mlPrediction?->sentence_sentiments ?? [];
    $transcriptObject = $record->transcript_object ?? [];
    $dbDuration = $record->duration_sec ?? 0;
@endphp

<div 
    x-data="audioPlayerUltra(@js($audioUrl), @js($sentimentData), @js($transcriptObject), @js($dbDuration), @js($callId))" 
    x-init="init()"
    class="w-full relative overflow-hidden"
    wire:ignore
>
    {{-- Aurora Background Effect --}}
    <div class="absolute inset-0 overflow-hidden rounded-3xl">
        <div class="absolute -inset-10 opacity-50">
            <div class="absolute top-0 -left-4 w-72 h-72 bg-purple-300 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob"></div>
            <div class="absolute top-0 -right-4 w-72 h-72 bg-yellow-300 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob animation-delay-2000"></div>
            <div class="absolute -bottom-8 left-20 w-72 h-72 bg-pink-300 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob animation-delay-4000"></div>
        </div>
    </div>
    
    {{-- Main Container with Glassmorphism --}}
    <div class="relative bg-white/60 dark:bg-gray-800/60 backdrop-blur-2xl rounded-3xl shadow-2xl border border-white/20 dark:border-gray-700/20 p-8 transform transition-all duration-500 hover:shadow-3xl">
        
        {{-- Header Section --}}
        <div class="flex items-center justify-between mb-8">
            <div class="flex items-center gap-4">
                {{-- Animated Icon --}}
                <div class="relative">
                    <div class="absolute inset-0 bg-gradient-to-br from-blue-500 to-purple-600 rounded-2xl blur-lg opacity-60 animate-pulse"></div>
                    <div class="relative p-3 bg-gradient-to-br from-blue-500 to-purple-600 rounded-2xl text-white transform transition-transform hover:scale-110">
                        <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M18 3a1 1 0 00-1.196-.98l-10 2A1 1 0 006 5v9.114A4.369 4.369 0 005 14c-1.657 0-3 .895-3 2s1.343 2 3 2 3-.895 3-2V7.82l8-1.6v5.894A4.37 4.37 0 0015 12c-1.657 0-3 .895-3 2s1.343 2 3 2 3-.895 3-2V3z" />
                        </svg>
                    </div>
                </div>
                
                {{-- Title with Gradient --}}
                <div>
                    <h3 class="text-2xl font-bold bg-gradient-to-r from-gray-900 via-gray-700 to-gray-900 dark:from-gray-100 dark:via-gray-300 dark:to-gray-100 bg-clip-text text-transparent">
                        Anruf-Aufzeichnung
                    </h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        Erweiterte Audioanalyse mit KI
                    </p>
                </div>
            </div>
            
            {{-- Duration Display with Status --}}
            <div class="flex items-center gap-6" x-show="!loading && audioUrl">
                {{-- Duration Comparison --}}
                <div class="text-right">
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-gray-500 dark:text-gray-400">Dauer:</span>
                        <div class="px-4 py-2 bg-gradient-to-r from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-800 rounded-full">
                            <span class="font-mono text-sm font-semibold" x-text="formatTime(currentTime)"></span>
                            <span class="text-gray-500 dark:text-gray-400 mx-1">/</span>
                            <span class="font-mono text-sm font-semibold" x-text="formatTime(actualDuration)"></span>
                        </div>
                    </div>
                    <div x-show="durationMismatch" class="text-xs text-amber-600 dark:text-amber-400 mt-1 flex items-center gap-1">
                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                        <span>DB: <span x-text="formatTime(dbDuration)"></span></span>
                    </div>
                </div>
                
                {{-- Audio Quality Badge --}}
                <div class="flex flex-col items-center">
                    <div class="px-3 py-1 bg-gradient-to-r from-green-400 to-green-600 text-white text-xs font-semibold rounded-full">
                        HD Audio
                    </div>
                </div>
            </div>
        </div>
        
        {{-- Advanced Controls Section --}}
        <div class="flex items-center justify-between gap-6 mb-8" x-show="audioUrl && !loading">
            {{-- Left Controls --}}
            <div class="flex items-center gap-4">
                {{-- 3D Play/Pause Button --}}
                <button 
                    @click="togglePlayPause()"
                    class="group relative"
                    :class="{ 'opacity-50': loading }"
                    :disabled="loading"
                >
                    <div class="absolute inset-0 bg-gradient-to-br from-blue-600 to-purple-700 rounded-full blur-lg group-hover:blur-xl transition-all duration-300 opacity-70"></div>
                    <div class="relative p-5 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full text-white shadow-2xl transform transition-all duration-300 group-hover:scale-110 group-active:scale-95">
                        <svg x-show="!playing" class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M6.3 2.841A1.5 1.5 0 004 4.11V15.89a1.5 1.5 0 002.3 1.269l9.344-5.89a1.5 1.5 0 000-2.538L6.3 2.84z" />
                        </svg>
                        <svg x-show="playing" class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zM7 8a1 1 0 012 0v4a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v4a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                    </div>
                </button>
                
                {{-- Skip Controls with Hover Effects --}}
                <div class="flex items-center bg-white/50 dark:bg-gray-800/50 rounded-2xl p-1 backdrop-blur-sm">
                    <button 
                        @click="skip(-30)"
                        class="p-3 rounded-xl hover:bg-white/70 dark:hover:bg-gray-700/70 text-gray-700 dark:text-gray-300 transition-all duration-200 group"
                        title="30 Sekunden zurück"
                    >
                        <div class="flex items-center gap-1">
                            <svg class="w-5 h-5 transform group-hover:scale-110 transition-transform" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M8.445 14.832A1 1 0 0010 14v-2.798l5.445 3.63A1 1 0 0017 14V6a1 1 0 00-1.555-.832L10 8.798V6a1 1 0 00-1.555-.832l-6 4a1 1 0 000 1.664l6 4z" />
                            </svg>
                            <span class="text-xs font-medium">30s</span>
                        </div>
                    </button>
                    
                    <button 
                        @click="skip(-10)"
                        class="p-3 rounded-xl hover:bg-white/70 dark:hover:bg-gray-700/70 text-gray-700 dark:text-gray-300 transition-all duration-200 group"
                        title="10 Sekunden zurück"
                    >
                        <div class="flex items-center gap-1">
                            <svg class="w-5 h-5 transform group-hover:scale-110 transition-transform" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M8.445 14.832A1 1 0 0010 14v-2.798l5.445 3.63A1 1 0 0017 14V6a1 1 0 00-1.555-.832L10 8.798V6a1 1 0 00-1.555-.832l-6 4a1 1 0 000 1.664l6 4z" />
                            </svg>
                            <span class="text-xs font-medium">10s</span>
                        </div>
                    </button>
                    
                    <div class="w-px h-8 bg-gray-300 dark:bg-gray-600 mx-1"></div>
                    
                    <button 
                        @click="skip(10)"
                        class="p-3 rounded-xl hover:bg-white/70 dark:hover:bg-gray-700/70 text-gray-700 dark:text-gray-300 transition-all duration-200 group"
                        title="10 Sekunden vor"
                    >
                        <div class="flex items-center gap-1">
                            <span class="text-xs font-medium">10s</span>
                            <svg class="w-5 h-5 transform group-hover:scale-110 transition-transform" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M4.555 5.168A1 1 0 003 6v8a1 1 0 001.555.832L10 11.202V14a1 1 0 001.555.832l6-4a1 1 0 000-1.664l-6-4A1 1 0 0010 6v2.798l-5.445-3.63z" />
                            </svg>
                        </div>
                    </button>
                    
                    <button 
                        @click="skip(30)"
                        class="p-3 rounded-xl hover:bg-white/70 dark:hover:bg-gray-700/70 text-gray-700 dark:text-gray-300 transition-all duration-200 group"
                        title="30 Sekunden vor"
                    >
                        <div class="flex items-center gap-1">
                            <span class="text-xs font-medium">30s</span>
                            <svg class="w-5 h-5 transform group-hover:scale-110 transition-transform" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M4.555 5.168A1 1 0 003 6v8a1 1 0 001.555.832L10 11.202V14a1 1 0 001.555.832l6-4a1 1 0 000-1.664l-6-4A1 1 0 0010 6v2.798l-5.445-3.63z" />
                            </svg>
                        </div>
                    </button>
                </div>
            </div>
            
            {{-- Right Controls --}}
            <div class="flex items-center gap-6">
                {{-- Volume Control --}}
                <div class="flex items-center gap-3">
                    <button @click="toggleMute()" class="p-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 transition-colors">
                        <svg x-show="!muted" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M9.383 3.076A1 1 0 0110 4v12a1 1 0 01-1.707.707L4.586 13H2a1 1 0 01-1-1V8a1 1 0 011-1h2.586l3.707-3.707a1 1 0 011.09-.217zM14.657 2.929a1 1 0 011.414 0A9.972 9.972 0 0119 10a9.972 9.972 0 01-2.929 7.071 1 1 0 01-1.414-1.414A7.971 7.971 0 0017 10c0-2.21-.894-4.208-2.343-5.657a1 1 0 010-1.414zm-2.829 2.828a1 1 0 011.415 0A5.983 5.983 0 0115 10a5.984 5.984 0 01-1.757 4.243 1 1 0 01-1.415-1.415A3.984 3.984 0 0013 10a3.983 3.983 0 00-1.172-2.828 1 1 0 010-1.415z" clip-rule="evenodd" />
                        </svg>
                        <svg x-show="muted" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M9.383 3.076A1 1 0 0110 4v12a1 1 0 01-1.707.707L4.586 13H2a1 1 0 01-1-1V8a1 1 0 011-1h2.586l3.707-3.707a1 1 0 011.09-.217zM12.293 7.293a1 1 0 011.414 0L15 8.586l1.293-1.293a1 1 0 111.414 1.414L16.414 10l1.293 1.293a1 1 0 01-1.414 1.414L15 11.414l-1.293 1.293a1 1 0 01-1.414-1.414L13.586 10l-1.293-1.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>
                    <input 
                        type="range" 
                        x-model="volume" 
                        @input="changeVolume()"
                        min="0" 
                        max="100" 
                        class="w-24 accent-purple-600"
                    >
                </div>
                
                {{-- Speed Control with Visual Indicators --}}
                <div class="flex items-center gap-3">
                    <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Geschwindigkeit</span>
                    <div class="flex bg-white/50 dark:bg-gray-800/50 rounded-xl p-1 backdrop-blur-sm">
                        @foreach([0.5, 0.75, 1, 1.25, 1.5, 2] as $speed)
                            <button 
                                @click="playbackRate = {{ $speed }}; changePlaybackRate()"
                                :class="playbackRate == {{ $speed }} ? 'bg-gradient-to-r from-purple-500 to-pink-500 text-white shadow-lg transform scale-105' : 'hover:bg-white/70 dark:hover:bg-gray-700/70 text-gray-700 dark:text-gray-300'"
                                class="px-3 py-2 rounded-lg text-sm font-semibold transition-all duration-200"
                            >{{ $speed }}x</button>
                        @endforeach
                    </div>
                </div>
                
                {{-- Additional Actions --}}
                <div class="flex items-center gap-2">
                    <button 
                        @click="downloadAudio()"
                        class="p-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 transition-colors"
                        title="Audio herunterladen"
                    >
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>
                    
                    <button 
                        @click="toggleFullscreen()"
                        class="p-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 transition-colors"
                        title="Vollbild"
                    >
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M3 4a1 1 0 011-1h4a1 1 0 010 2H6.414l2.293 2.293a1 1 0 01-1.414 1.414L5 6.414V8a1 1 0 01-2 0V4zm9 1a1 1 0 110-2h4a1 1 0 011 1v4a1 1 0 11-2 0V6.414l-2.293 2.293a1 1 0 11-1.414-1.414L13.586 5H12zm-9 9a1 1 0 112 0v1.586l2.293-2.293a1 1 0 111.414 1.414L6.414 17H8a1 1 0 110 2H4a1 1 0 01-1-1v-4zm13-1a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 110-2h1.586l-2.293-2.293a1 1 0 111.414-1.414L15 15.586V14a1 1 0 011-1z" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
        
        {{-- Waveform Visualization --}}
        <div class="relative mb-6">
            {{-- Loading State --}}
            <div x-show="loading" class="absolute inset-0 flex items-center justify-center bg-gradient-to-r from-gray-100 to-gray-200 dark:from-gray-800 dark:to-gray-900 rounded-2xl">
                <div class="text-center">
                    <div class="relative">
                        <div class="w-20 h-20 border-4 border-purple-200 dark:border-purple-800 rounded-full animate-pulse"></div>
                        <div class="absolute top-0 left-0 w-20 h-20 border-4 border-transparent border-t-purple-600 rounded-full animate-spin"></div>
                    </div>
                    <p class="mt-4 text-sm font-medium text-gray-600 dark:text-gray-400">Audio wird geladen...</p>
                </div>
            </div>
            
            {{-- Waveform Container --}}
            <div id="waveform-ultra-{{ $callId }}" x-ref="waveformContainer" 
                 class="relative w-full h-40 bg-gradient-to-b from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800 rounded-2xl overflow-hidden transform transition-all duration-300 hover:shadow-inner" 
                 x-show="!loading">
                {{-- Decorative Grid --}}
                <div class="absolute inset-0 opacity-10">
                    <div class="grid grid-cols-12 h-full">
                        @for($i = 0; $i < 12; $i++)
                            <div class="border-r border-gray-400 dark:border-gray-600"></div>
                        @endfor
                    </div>
                </div>
            </div>
            
            {{-- Progress Time Tooltip --}}
            <div 
                x-show="showTooltip" 
                x-ref="tooltip"
                class="absolute top-0 transform -translate-x-1/2 -translate-y-full mb-2 px-2 py-1 bg-gray-900 dark:bg-gray-100 text-white dark:text-gray-900 text-xs font-mono rounded-lg opacity-0 transition-opacity duration-200"
                :style="`left: ${tooltipPosition}px; opacity: ${showTooltip ? 1 : 0}`"
                x-text="tooltipTime"
            ></div>
            
            {{-- Sentiment Timeline --}}
            <div class="w-full h-10 mt-3" x-show="!loading && sentimentData.length > 0">
                <canvas x-ref="sentimentCanvas" class="w-full h-full rounded-lg"></canvas>
            </div>
        </div>
        
        {{-- Enhanced Sentiment Legend --}}
        <div class="flex items-center justify-center gap-8 mb-6" x-show="sentimentData.length > 0">
            @foreach([
                ['positive', 'Positiv', 'from-green-400 to-emerald-600', 'bg-green-500'],
                ['neutral', 'Neutral', 'from-gray-400 to-gray-600', 'bg-gray-400'],
                ['negative', 'Negativ', 'from-red-400 to-rose-600', 'bg-red-500']
            ] as $sentiment)
                <div class="flex items-center gap-3 group cursor-pointer" @click="filterBySentiment('{{ $sentiment[0] }}')">
                    <div class="relative">
                        <div class="absolute inset-0 bg-gradient-to-r {{ $sentiment[2] }} rounded-lg blur opacity-60 group-hover:opacity-100 transition-opacity"></div>
                        <div class="relative w-6 h-6 {{ $sentiment[3] }} rounded-lg shadow-lg transform group-hover:scale-110 transition-transform"></div>
                    </div>
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300 group-hover:text-gray-900 dark:group-hover:text-gray-100 transition-colors">
                        {{ $sentiment[1] }}
                        <span class="text-xs text-gray-500 dark:text-gray-400 ml-1" x-text="getSentimentCount('{{ $sentiment[0] }}')"></span>
                    </span>
                </div>
            @endforeach
        </div>
        
        {{-- Keyboard Shortcuts Info --}}
        <div class="text-center text-xs text-gray-500 dark:text-gray-400">
            <span class="font-medium">Tastenkürzel:</span>
            <span class="mx-2">Space = Play/Pause</span>
            <span class="mx-2">← → = 10s springen</span>
            <span class="mx-2">↑ ↓ = Lautstärke</span>
            <span class="mx-2">F = Vollbild</span>
        </div>
    </div>
    
    {{-- Hidden Audio Element --}}
    <audio x-ref="audio" :src="audioUrl" preload="metadata"></audio>
</div>

@pushOnce('scripts')
<script src="https://unpkg.com/wavesurfer.js@7.7.3/dist/wavesurfer.min.js"></script>
@endPushOnce

@push('scripts')
<script>
function audioPlayerUltra(audioUrl, sentimentData, transcriptObject, dbDuration, callId) {
    return {
        audioUrl: audioUrl,
        sentimentData: sentimentData || [],
        transcriptObject: transcriptObject || [],
        dbDuration: dbDuration || 0,
        actualDuration: 0,
        durationMismatch: false,
        callId: callId,
        wavesurfer: null,
        playing: false,
        loading: true,
        currentTime: 0,
        playbackRate: 1,
        volume: 100,
        muted: false,
        currentSentenceIndex: -1,
        showTooltip: false,
        tooltipPosition: 0,
        tooltipTime: '0:00',
        
        init() {
            if (!this.audioUrl) {
                this.loading = false;
                this.showFallbackMessage();
                return;
            }
            
            // Store instance globally for transcript sync
            window.audioPlayerInstance = this;
            
            // Initialize keyboard shortcuts
            this.setupKeyboardShortcuts();
            
            this.checkAudioUrl();
            
            // Cleanup on Alpine destroy
            this.$cleanup = () => {
                if (this.wavesurfer) {
                    this.wavesurfer.destroy();
                    this.wavesurfer = null;
                }
                if (window.audioPlayerInstance === this) {
                    window.audioPlayerInstance = null;
                }
                document.removeEventListener('keydown', this.handleKeydown);
            };
        },
        
        setupKeyboardShortcuts() {
            this.handleKeydown = (e) => {
                // Ignore if user is typing in an input
                if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;
                
                switch(e.key) {
                    case ' ':
                        e.preventDefault();
                        this.togglePlayPause();
                        break;
                    case 'ArrowLeft':
                        e.preventDefault();
                        this.skip(-10);
                        break;
                    case 'ArrowRight':
                        e.preventDefault();
                        this.skip(10);
                        break;
                    case 'ArrowUp':
                        e.preventDefault();
                        this.volume = Math.min(100, this.volume + 10);
                        this.changeVolume();
                        break;
                    case 'ArrowDown':
                        e.preventDefault();
                        this.volume = Math.max(0, this.volume - 10);
                        this.changeVolume();
                        break;
                    case 'f':
                    case 'F':
                        e.preventDefault();
                        this.toggleFullscreen();
                        break;
                }
            };
            
            document.addEventListener('keydown', this.handleKeydown);
        },
        
        checkAudioUrl() {
            const audio = new Audio();
            audio.crossOrigin = 'anonymous';
            
            audio.onerror = () => {
                console.warn('Audio URL not accessible, showing fallback message');
                this.loading = false;
                this.showFallbackMessage();
            };
            
            audio.onloadedmetadata = () => {
                this.actualDuration = audio.duration;
                this.durationMismatch = Math.abs(this.actualDuration - this.dbDuration) > 5;
                this.initializeWaveSurfer();
            };
            
            audio.src = this.audioUrl;
        },
        
        showFallbackMessage() {
            const container = this.$refs.waveformContainer;
            if (container) {
                container.innerHTML = `
                    <div class="flex items-center justify-center h-full">
                        <div class="text-center">
                            <div class="relative mb-4">
                                <div class="w-24 h-24 bg-gradient-to-br from-gray-200 to-gray-300 dark:from-gray-700 dark:to-gray-800 rounded-full flex items-center justify-center">
                                    <svg class="w-12 h-12 text-gray-400 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"></path>
                                    </svg>
                                </div>
                            </div>
                            <h4 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-1">Keine Aufzeichnung verfügbar</h4>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Für diesen Anruf wurde keine Audio-Aufzeichnung gespeichert</p>
                        </div>
                    </div>
                `;
            }
        },
        
        initializeWaveSurfer() {
            // Ensure we destroy any existing instance
            if (this.wavesurfer) {
                this.wavesurfer.destroy();
                this.wavesurfer = null;
            }
            
            // Initialize WaveSurfer with enhanced options
            this.wavesurfer = WaveSurfer.create({
                container: this.$refs.waveformContainer,
                waveColor: 'url(#gradient-wave)',
                progressColor: 'url(#gradient-progress)',
                cursorColor: '#8B5CF6',
                barWidth: 3,
                barRadius: 5,
                barGap: 2,
                responsive: true,
                height: 160,
                normalize: true,
                backend: 'MediaElement',
                mediaControls: false,
                interact: true,
                dragToSeek: true,
                minPxPerSec: 50,
                autoCenter: true,
                partialRender: true
            });
            
            // Create gradients for waveform
            const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
            svg.innerHTML = `
                <defs>
                    <linearGradient id="gradient-wave" x1="0%" y1="0%" x2="0%" y2="100%">
                        <stop offset="0%" style="stop-color:#E0E7FF;stop-opacity:1" />
                        <stop offset="100%" style="stop-color:#C7D2FE;stop-opacity:1" />
                    </linearGradient>
                    <linearGradient id="gradient-progress" x1="0%" y1="0%" x2="0%" y2="100%">
                        <stop offset="0%" style="stop-color:#8B5CF6;stop-opacity:1" />
                        <stop offset="100%" style="stop-color:#7C3AED;stop-opacity:1" />
                    </linearGradient>
                </defs>
            `;
            this.$refs.waveformContainer.appendChild(svg);
            
            // Load audio
            this.wavesurfer.load(this.audioUrl);
            
            // Mouse hover tooltip
            this.$refs.waveformContainer.addEventListener('mousemove', (e) => {
                const rect = this.$refs.waveformContainer.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const progress = x / rect.width;
                const time = progress * this.actualDuration;
                
                this.showTooltip = true;
                this.tooltipPosition = x;
                this.tooltipTime = this.formatTime(time);
            });
            
            this.$refs.waveformContainer.addEventListener('mouseleave', () => {
                this.showTooltip = false;
            });
            
            // Event listeners
            this.wavesurfer.on('ready', () => {
                this.loading = false;
                this.actualDuration = this.wavesurfer.getDuration();
                this.durationMismatch = Math.abs(this.actualDuration - this.dbDuration) > 5;
                console.log('Audio ready - Actual duration:', this.actualDuration, 'DB duration:', this.dbDuration);
                this.drawSentimentTimeline();
                
                // Set initial volume
                this.changeVolume();
            });
            
            this.wavesurfer.on('audioprocess', () => {
                this.currentTime = this.wavesurfer.getCurrentTime();
                this.updateSentimentProgress();
                this.highlightCurrentSentence();
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
            
            // Listen for sentence selection events from transcript
            window.addEventListener('sentence-selected', (event) => {
                if (event.detail && event.detail.startTime !== undefined) {
                    this.seekTo(parseFloat(event.detail.startTime));
                    if (!this.playing) {
                        this.togglePlayPause();
                    }
                }
            });
        },
        
        highlightCurrentSentence() {
            if (!this.sentimentData || this.sentimentData.length === 0) return;
            
            // Find current sentence based on time
            let currentIndex = -1;
            for (let i = 0; i < this.sentimentData.length; i++) {
                const sentence = this.sentimentData[i];
                if (sentence.start_time !== undefined && sentence.end_time !== undefined) {
                    if (this.currentTime >= sentence.start_time && this.currentTime <= sentence.end_time) {
                        currentIndex = i;
                        break;
                    }
                }
            }
            
            // Only update if changed
            if (currentIndex !== this.currentSentenceIndex) {
                this.currentSentenceIndex = currentIndex;
                
                // Dispatch event to transcript viewer
                window.dispatchEvent(new CustomEvent('audio-time-update', {
                    detail: {
                        currentTime: this.currentTime,
                        sentenceIndex: currentIndex,
                        sentence: currentIndex >= 0 ? this.sentimentData[currentIndex] : null
                    }
                }));
            }
        },
        
        togglePlayPause() {
            if (this.wavesurfer) {
                this.wavesurfer.playPause();
            }
        },
        
        skip(seconds) {
            if (this.wavesurfer) {
                this.wavesurfer.skip(seconds);
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
        
        toggleMute() {
            this.muted = !this.muted;
            if (this.wavesurfer) {
                this.wavesurfer.setMute(this.muted);
            }
        },
        
        seekTo(time) {
            if (this.wavesurfer && time >= 0 && time <= this.actualDuration) {
                this.wavesurfer.seekTo(time / this.actualDuration);
            }
        },
        
        formatTime(seconds) {
            const minutes = Math.floor(seconds / 60);
            const secs = Math.floor(seconds % 60);
            return `${minutes}:${secs.toString().padStart(2, '0')}`;
        },
        
        getSentimentCount(sentiment) {
            return `(${this.sentimentData.filter(s => s.sentiment === sentiment).length})`;
        },
        
        filterBySentiment(sentiment) {
            // TODO: Implement sentiment filtering
            console.log('Filter by sentiment:', sentiment);
        },
        
        downloadAudio() {
            if (this.audioUrl) {
                const a = document.createElement('a');
                a.href = this.audioUrl;
                a.download = `call-${this.callId}-recording.wav`;
                a.click();
            }
        },
        
        toggleFullscreen() {
            const elem = this.$el;
            if (!document.fullscreenElement) {
                elem.requestFullscreen().catch(err => {
                    console.error('Error attempting to enable fullscreen:', err);
                });
            } else {
                document.exitFullscreen();
            }
        },
        
        drawSentimentTimeline() {
            const canvas = this.$refs.sentimentCanvas;
            if (!canvas || this.sentimentData.length === 0) return;
            
            const ctx = canvas.getContext('2d');
            const width = canvas.offsetWidth;
            const height = canvas.offsetHeight;
            
            canvas.width = width;
            canvas.height = height;
            
            // Create gradient background
            const bgGradient = ctx.createLinearGradient(0, 0, width, 0);
            bgGradient.addColorStop(0, 'rgba(229, 231, 235, 0.5)');
            bgGradient.addColorStop(1, 'rgba(209, 213, 219, 0.5)');
            ctx.fillStyle = bgGradient;
            ctx.fillRect(0, 0, width, height);
            
            // Draw sentiment blocks with gradients
            this.sentimentData.forEach(segment => {
                if (segment.start_time !== undefined && segment.end_time !== undefined) {
                    const startX = (segment.start_time / this.actualDuration) * width;
                    const endX = (segment.end_time / this.actualDuration) * width;
                    const blockWidth = endX - startX;
                    
                    // Create gradient for each sentiment
                    const gradient = ctx.createLinearGradient(startX, 0, startX, height);
                    switch(segment.sentiment) {
                        case 'positive':
                            gradient.addColorStop(0, '#10b981');
                            gradient.addColorStop(1, '#059669');
                            break;
                        case 'negative':
                            gradient.addColorStop(0, '#ef4444');
                            gradient.addColorStop(1, '#dc2626');
                            break;
                        default:
                            gradient.addColorStop(0, '#9ca3af');
                            gradient.addColorStop(1, '#6b7280');
                    }
                    
                    ctx.fillStyle = gradient;
                    ctx.fillRect(startX, 0, blockWidth, height);
                    
                    // Add subtle border
                    ctx.strokeStyle = 'rgba(0, 0, 0, 0.1)';
                    ctx.lineWidth = 1;
                    ctx.strokeRect(startX, 0, blockWidth, height);
                }
            });
            
            // Add rounded corners
            ctx.globalCompositeOperation = 'destination-in';
            ctx.fillStyle = 'black';
            ctx.beginPath();
            ctx.roundRect(0, 0, width, height, 8);
            ctx.fill();
            ctx.globalCompositeOperation = 'source-over';
        },
        
        updateSentimentProgress() {
            const canvas = this.$refs.sentimentCanvas;
            if (!canvas || this.sentimentData.length === 0) return;
            
            const ctx = canvas.getContext('2d');
            const width = canvas.width;
            const height = canvas.height;
            
            this.drawSentimentTimeline();
            
            // Draw progress indicator
            const progressX = (this.currentTime / this.actualDuration) * width;
            
            // Glow effect
            const glowGradient = ctx.createRadialGradient(progressX, height/2, 0, progressX, height/2, 20);
            glowGradient.addColorStop(0, 'rgba(139, 92, 246, 0.8)');
            glowGradient.addColorStop(1, 'rgba(139, 92, 246, 0)');
            ctx.fillStyle = glowGradient;
            ctx.fillRect(progressX - 20, 0, 40, height);
            
            // Progress line
            ctx.strokeStyle = '#8b5cf6';
            ctx.lineWidth = 3;
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
@keyframes blob {
    0% {
        transform: translate(0px, 0px) scale(1);
    }
    33% {
        transform: translate(30px, -50px) scale(1.1);
    }
    66% {
        transform: translate(-20px, 20px) scale(0.9);
    }
    100% {
        transform: translate(0px, 0px) scale(1);
    }
}

.animate-blob {
    animation: blob 7s infinite;
}

.animation-delay-2000 {
    animation-delay: 2s;
}

.animation-delay-4000 {
    animation-delay: 4s;
}

/* Custom scrollbar for waveform */
#waveform-ultra-{{ $callId }} wave {
    scrollbar-width: thin;
    scrollbar-color: rgba(139, 92, 246, 0.3) transparent;
}

#waveform-ultra-{{ $callId }} wave::-webkit-scrollbar {
    height: 6px;
}

#waveform-ultra-{{ $callId }} wave::-webkit-scrollbar-track {
    background: transparent;
}

#waveform-ultra-{{ $callId }} wave::-webkit-scrollbar-thumb {
    background: rgba(139, 92, 246, 0.3);
    border-radius: 3px;
}

#waveform-ultra-{{ $callId }} wave::-webkit-scrollbar-thumb:hover {
    background: rgba(139, 92, 246, 0.5);
}

/* Fullscreen styles */
:fullscreen .audio-player-ultra {
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(0, 0, 0, 0.9);
    padding: 2rem;
}

/* Canvas rounded corners polyfill for older browsers */
@supports not ((-webkit-clip-path: inset(0 round 8px)) or (clip-path: inset(0 round 8px))) {
    canvas {
        mask-image: radial-gradient(white, black);
        -webkit-mask-image: radial-gradient(white, black);
    }
}
</style>