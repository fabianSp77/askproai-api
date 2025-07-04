@php
    $record = $getRecord();
    $audioUrl = $record->audio_url ?? $record->recording_url ?? ($record->webhook_data['recording_url'] ?? null);
    $callId = $record->id;
    $sentimentData = $record->mlPrediction?->sentence_sentiments ?? [];
    $transcriptObject = $record->transcript_object ?? [];
    $dbDuration = $record->duration_sec ?? 0;
@endphp

<div 
    x-data="audioDurationFixed(@js($audioUrl), @js($sentimentData), @js($transcriptObject), @js($dbDuration), @js($callId))" 
    x-init="init()"
    class="w-full bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6"
    wire:ignore
>
    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Anruf-Aufzeichnung</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                Call ID: {{ $callId }}
            </p>
        </div>
        
        {{-- Duration Info --}}
        <div class="text-right">
            <div class="flex items-center gap-2">
                <span class="text-sm text-gray-500">Dauer:</span>
                <div class="px-3 py-1 bg-gray-100 dark:bg-gray-700 rounded-full">
                    <span class="font-mono text-sm" x-text="formatTime(currentTime)"></span>
                    <span class="text-gray-400 mx-1">/</span>
                    <span class="font-mono text-sm font-semibold" x-text="formatTime(actualDuration)"></span>
                </div>
            </div>
            
            {{-- Duration Mismatch Warning --}}
            <div x-show="durationMismatch" class="text-xs text-amber-600 dark:text-amber-400 mt-2 flex items-center gap-1">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
                <span>DB zeigt <span x-text="formatTime(dbDuration)"></span> - Ratio: <span x-text="(actualDuration/dbDuration).toFixed(3)"></span></span>
            </div>
        </div>
    </div>
    
    {{-- Waveform Container --}}
    <div class="mb-6">
        <div id="waveform-{{ $callId }}" class="w-full h-32 bg-gray-50 dark:bg-gray-900 rounded-lg"></div>
        
        {{-- Progress Bar --}}
        <div class="relative h-1 bg-gray-200 dark:bg-gray-700 rounded-full mt-4">
            <div 
                class="absolute top-0 left-0 h-full bg-blue-500 rounded-full transition-all duration-100"
                :style="`width: ${(currentTime / actualDuration) * 100}%`"
            ></div>
        </div>
    </div>
    
    {{-- Controls --}}
    <div class="flex items-center justify-between">
        {{-- Play Controls --}}
        <div class="flex items-center gap-4">
            <button 
                @click="togglePlayPause()"
                class="p-3 bg-blue-500 hover:bg-blue-600 text-white rounded-full transition-colors"
                :disabled="loading || !audioUrl"
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
                @click="skip(-10)"
                class="p-2 text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200"
                title="10 Sekunden zurück"
            >
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M8.445 14.832A1 1 0 0010 14v-2.798l5.445 3.63A1 1 0 0017 14V6a1 1 0 00-1.555-.832L10 8.798V6a1 1 0 00-1.555-.832l-6 4a1 1 0 000 1.664l6 4z" />
                </svg>
            </button>
            
            <button 
                @click="skip(10)"
                class="p-2 text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200"
                title="10 Sekunden vor"
            >
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M4.555 5.168A1 1 0 003 6v8a1 1 0 001.555.832L10 11.202V14a1 1 0 001.555.832l6-4a1 1 0 000-1.664l-6-4A1 1 0 0010 6v2.798l-5.445-3.63z" />
                </svg>
            </button>
        </div>
        
        {{-- Volume & Speed --}}
        <div class="flex items-center gap-4">
            {{-- Speed Control --}}
            <select 
                x-model="playbackRate" 
                @change="changePlaybackRate()"
                class="text-sm border-gray-300 dark:border-gray-600 rounded-md"
            >
                <option value="0.5">0.5x</option>
                <option value="0.75">0.75x</option>
                <option value="1">1x</option>
                <option value="1.25">1.25x</option>
                <option value="1.5">1.5x</option>
                <option value="2">2x</option>
            </select>
            
            {{-- Volume --}}
            <div class="flex items-center gap-2">
                <button @click="toggleMute()" class="text-gray-600 dark:text-gray-400">
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
                    class="w-24"
                >
            </div>
        </div>
    </div>
    
    {{-- Loading/Error States --}}
    <div x-show="loading && audioUrl" class="text-center py-4 text-gray-500">
        <svg class="animate-spin h-8 w-8 mx-auto text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <p class="mt-2">Audio wird geladen...</p>
    </div>
    
    <div x-show="!audioUrl" class="text-center py-8 text-gray-500">
        <svg class="w-12 h-12 mx-auto mb-2" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
        </svg>
        <p>Keine Audio-URL verfügbar</p>
    </div>
    
    <div x-show="error" class="text-center py-4 text-red-500">
        <p x-text="errorMessage"></p>
    </div>
</div>

@pushOnce('scripts')
<script src="https://unpkg.com/wavesurfer.js@7.7.3/dist/wavesurfer.min.js"></script>
@endPushOnce

@push('scripts')
<script>
function audioDurationFixed(audioUrl, sentimentData, transcriptObject, dbDuration, callId) {
    return {
        // Data properties
        audioUrl: audioUrl,
        sentimentData: sentimentData || [],
        transcriptObject: transcriptObject || [],
        dbDuration: dbDuration || 0,
        callId: callId,
        
        // Player state
        wavesurfer: null,
        playing: false,
        loading: true,
        currentTime: 0,
        actualDuration: 0,
        durationMismatch: false,
        error: false,
        errorMessage: '',
        
        // Controls
        playbackRate: 1,
        volume: 100,
        muted: false,
        
        init() {
            if (!this.audioUrl) {
                this.loading = false;
                return;
            }
            
            // Initialize WaveSurfer
            this.wavesurfer = WaveSurfer.create({
                container: `#waveform-${this.callId}`,
                waveColor: 'rgb(147, 197, 253)',
                progressColor: 'rgb(59, 130, 246)',
                cursorColor: 'rgb(30, 64, 175)',
                barWidth: 2,
                barRadius: 3,
                cursorWidth: 1,
                height: 128,
                barGap: 3,
                responsive: true,
                normalize: true,
                backend: 'WebAudio',
                mediaControls: false,
                interact: true,
                dragToSeek: true,
            });
            
            // Load audio
            this.wavesurfer.load(this.audioUrl);
            
            // Event listeners
            this.wavesurfer.on('ready', () => {
                this.loading = false;
                this.actualDuration = this.wavesurfer.getDuration();
                console.log(`Audio loaded - Actual duration: ${this.actualDuration} DB duration: ${this.dbDuration}`);
                
                // Check for duration mismatch
                if (this.dbDuration > 0 && Math.abs(this.actualDuration - this.dbDuration) > 2) {
                    this.durationMismatch = true;
                    console.warn(`Duration mismatch! DB: ${this.dbDuration}s, Actual: ${this.actualDuration}s`);
                    
                    // Adjust sentiment timestamps proportionally
                    if (this.sentimentData.length > 0) {
                        const ratio = this.actualDuration / this.dbDuration;
                        console.log(`Adjusting sentiment timestamps with ratio: ${ratio}`);
                        this.sentimentData = this.sentimentData.map(s => ({
                            ...s,
                            start_time: s.start_time * ratio,
                            end_time: s.end_time * ratio
                        }));
                    }
                }
            });
            
            this.wavesurfer.on('audioprocess', () => {
                this.currentTime = this.wavesurfer.getCurrentTime();
            });
            
            this.wavesurfer.on('play', () => {
                this.playing = true;
            });
            
            this.wavesurfer.on('pause', () => {
                this.playing = false;
            });
            
            this.wavesurfer.on('error', (e) => {
                console.error('WaveSurfer error:', e);
                this.error = true;
                this.errorMessage = 'Fehler beim Laden der Audio-Datei';
                this.loading = false;
            });
            
            // Keyboard shortcuts
            document.addEventListener('keydown', (e) => {
                if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;
                
                switch(e.key) {
                    case ' ':
                        e.preventDefault();
                        this.togglePlayPause();
                        break;
                    case 'ArrowLeft':
                        e.preventDefault();
                        this.skip(-5);
                        break;
                    case 'ArrowRight':
                        e.preventDefault();
                        this.skip(5);
                        break;
                }
            });
        },
        
        togglePlayPause() {
            if (this.wavesurfer) {
                this.wavesurfer.playPause();
            }
        },
        
        skip(seconds) {
            if (this.wavesurfer) {
                const newTime = this.currentTime + seconds;
                const clampedTime = Math.max(0, Math.min(newTime, this.actualDuration));
                this.wavesurfer.seekTo(clampedTime / this.actualDuration);
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
        
        formatTime(seconds) {
            const minutes = Math.floor(seconds / 60);
            const secs = Math.floor(seconds % 60);
            return `${minutes}:${secs.toString().padStart(2, '0')}`;
        }
    };
}
</script>
@endpush

<style>
/* Additional styles for the waveform */
#waveform-{{ $callId }} {
    overflow: hidden;
    position: relative;
}

#waveform-{{ $callId }}::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(to right, transparent, rgba(59, 130, 246, 0.1), transparent);
    pointer-events: none;
    z-index: 1;
}
</style>