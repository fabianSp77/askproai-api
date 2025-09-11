<div x-data="audioPlayer()" 
     x-init="init()" 
     class="relative bg-gradient-to-br from-indigo-50/80 via-purple-50/80 to-pink-50/80 dark:from-indigo-950/40 dark:via-purple-950/40 dark:to-pink-950/40 backdrop-blur-xl rounded-3xl p-8 shadow-2xl border border-white/20 dark:border-gray-700/20">
    
    {{-- Background Effects --}}
    <div class="absolute inset-0 rounded-3xl overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-r from-indigo-500/10 via-purple-500/10 to-pink-500/10 animate-gradient-x"></div>
        <div class="absolute -top-20 -left-20 w-60 h-60 bg-indigo-400 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-blob"></div>
        <div class="absolute -bottom-20 -right-20 w-60 h-60 bg-purple-400 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-blob animation-delay-2000"></div>
    </div>
    
    <div class="relative z-10">
        {{-- Title Section --}}
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center space-x-3">
                <div class="p-3 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl shadow-lg">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Anrufaufzeichnung</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $this->formatTime($duration) }} Gesamtdauer</p>
                </div>
            </div>
            
            {{-- Playback Speed Control --}}
            <div class="flex items-center space-x-2">
                <button @click="setPlaybackRate(0.5)" 
                        :class="playbackRate === 0.5 ? 'bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400' : 'text-gray-600 dark:text-gray-400'"
                        class="px-3 py-1.5 rounded-lg text-sm font-medium transition-all hover:bg-gray-100 dark:hover:bg-gray-800">
                    0.5x
                </button>
                <button @click="setPlaybackRate(1)" 
                        :class="playbackRate === 1 ? 'bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400' : 'text-gray-600 dark:text-gray-400'"
                        class="px-3 py-1.5 rounded-lg text-sm font-medium transition-all hover:bg-gray-100 dark:hover:bg-gray-800">
                    1x
                </button>
                <button @click="setPlaybackRate(1.5)" 
                        :class="playbackRate === 1.5 ? 'bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400' : 'text-gray-600 dark:text-gray-400'"
                        class="px-3 py-1.5 rounded-lg text-sm font-medium transition-all hover:bg-gray-100 dark:hover:bg-gray-800">
                    1.5x
                </button>
                <button @click="setPlaybackRate(2)" 
                        :class="playbackRate === 2 ? 'bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400' : 'text-gray-600 dark:text-gray-400'"
                        class="px-3 py-1.5 rounded-lg text-sm font-medium transition-all hover:bg-gray-100 dark:hover:bg-gray-800">
                    2x
                </button>
            </div>
        </div>
        
        {{-- Waveform Visualization --}}
        <div class="relative mb-6 cursor-pointer" @click="seekToPosition($event)">
            <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent rounded-xl"></div>
            
            {{-- Progress Overlay --}}
            <div class="absolute inset-y-0 left-0 bg-gradient-to-r from-indigo-500/30 to-purple-500/30 rounded-l-xl transition-all duration-300"
                 :style="`width: ${progressPercentage}%`"></div>
            
            {{-- Waveform Bars --}}
            <div class="relative flex items-center justify-between h-32 px-2">
                @foreach($waveformData as $index => $amplitude)
                    <div class="relative flex-1 mx-px group">
                        {{-- Main Bar --}}
                        <div class="absolute bottom-0 w-full bg-gradient-to-t transition-all duration-300"
                             :class="progressPercentage > {{ $index }} ? 'from-indigo-500 to-indigo-400' : 'from-gray-400 to-gray-300 dark:from-gray-600 dark:to-gray-500'"
                             style="height: {{ $amplitude * 100 }}%; opacity: {{ 0.6 + $amplitude * 0.4 }}">
                        </div>
                        
                        {{-- Hover Effect --}}
                        <div class="absolute bottom-0 w-full bg-gradient-to-t from-purple-500 to-purple-400 opacity-0 group-hover:opacity-100 transition-opacity duration-200"
                             style="height: {{ $amplitude * 100 }}%">
                        </div>
                        
                        {{-- Peak Indicator --}}
                        @if(in_array($index, $peaks))
                            <div class="absolute top-0 left-1/2 transform -translate-x-1/2 w-1 h-1 bg-red-500 rounded-full animate-pulse"></div>
                        @endif
                    </div>
                @endforeach
            </div>
            
            {{-- Time Markers --}}
            <div class="absolute bottom-0 left-0 right-0 flex justify-between px-2 text-xs text-gray-500 dark:text-gray-400">
                <span>0:00</span>
                <span>{{ $this->formatTime($duration / 4) }}</span>
                <span>{{ $this->formatTime($duration / 2) }}</span>
                <span>{{ $this->formatTime(3 * $duration / 4) }}</span>
                <span>{{ $this->formatTime($duration) }}</span>
            </div>
            
            {{-- Current Time Indicator --}}
            <div class="absolute top-0 bottom-0 w-0.5 bg-white shadow-lg transition-all duration-300"
                 :style="`left: ${progressPercentage}%`">
                <div class="absolute -top-2 left-1/2 transform -translate-x-1/2 bg-white dark:bg-gray-800 rounded-lg px-2 py-1 text-xs font-medium shadow-lg">
                    <span x-text="formatTime(currentTime)"></span>
                </div>
            </div>
        </div>
        
        {{-- Control Panel --}}
        <div class="flex items-center justify-between">
            {{-- Left Controls --}}
            <div class="flex items-center space-x-2">
                {{-- Skip Backward --}}
                <button wire:click="skipBackward" 
                        class="group p-3 bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm rounded-xl shadow-md hover:shadow-xl transform transition-all hover:scale-110 duration-200">
                    <svg class="w-5 h-5 text-gray-700 dark:text-gray-300 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12.066 11.2a1 1 0 000 1.6l5.334 4A1 1 0 0019 16V8a1 1 0 00-1.6-.8l-5.333 4zM4.066 11.2a1 1 0 000 1.6l5.334 4A1 1 0 0011 16V8a1 1 0 00-1.6-.8l-5.334 4z"></path>
                    </svg>
                </button>
                
                {{-- Play/Pause Button --}}
                <button wire:click="togglePlayback" 
                        class="group relative p-5 transform transition-all hover:scale-110 duration-200">
                    <div class="absolute inset-0 bg-gradient-to-r from-indigo-500 to-purple-600 rounded-2xl shadow-xl group-hover:shadow-2xl transition-shadow"></div>
                    <div class="absolute inset-0 bg-gradient-to-r from-indigo-600 to-purple-700 rounded-2xl opacity-0 group-hover:opacity-100 transition-opacity"></div>
                    <svg x-show="!isPlaying" class="relative w-8 h-8 text-white" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd"></path>
                    </svg>
                    <svg x-show="isPlaying" class="relative w-8 h-8 text-white" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zM7 8a1 1 0 012 0v4a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v4a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                    </svg>
                </button>
                
                {{-- Skip Forward --}}
                <button wire:click="skipForward" 
                        class="group p-3 bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm rounded-xl shadow-md hover:shadow-xl transform transition-all hover:scale-110 duration-200">
                    <svg class="w-5 h-5 text-gray-700 dark:text-gray-300 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.933 12.8a1 1 0 000-1.6L6.6 7.2A1 1 0 005 8v8a1 1 0 001.6.8l5.333-4zM19.933 12.8a1 1 0 000-1.6l-5.333-4A1 1 0 0013 8v8a1 1 0 001.6.8l5.333-4z"></path>
                    </svg>
                </button>
            </div>
            
            {{-- Center Time Display --}}
            <div class="flex items-center space-x-2 text-sm font-medium">
                <span class="text-gray-900 dark:text-white" x-text="formatTime(currentTime)"></span>
                <span class="text-gray-400">/</span>
                <span class="text-gray-600 dark:text-gray-400">{{ $this->formatTime($duration) }}</span>
            </div>
            
            {{-- Right Controls --}}
            <div class="flex items-center space-x-4">
                {{-- Volume Control --}}
                <div class="flex items-center space-x-2">
                    <button @click="toggleMute()" class="p-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">
                        <svg x-show="volume > 0" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"></path>
                        </svg>
                        <svg x-show="volume === 0" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" clip-rule="evenodd"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2"></path>
                        </svg>
                    </button>
                    <input type="range" 
                           x-model="volume" 
                           @input="setVolume($event.target.value)"
                           min="0" 
                           max="100" 
                           class="w-24 h-1 bg-gray-200 dark:bg-gray-700 rounded-lg appearance-none cursor-pointer accent-indigo-600">
                </div>
                
                {{-- Download Button --}}
                <button wire:click="downloadRecording" 
                        class="group p-3 bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm rounded-xl shadow-md hover:shadow-xl transform transition-all hover:scale-110 duration-200">
                    <svg class="w-5 h-5 text-gray-700 dark:text-gray-300 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                    </svg>
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function audioPlayer() {
    return {
        isPlaying: @entangle('isPlaying'),
        currentTime: @entangle('currentTime'),
        volume: @entangle('volume'),
        playbackRate: @entangle('playbackRate'),
        duration: {{ $duration }},
        progressPercentage: 0,
        audio: null,
        
        init() {
            this.audio = new Audio('{{ $audioUrl }}');
            this.setupAudioListeners();
            this.updateProgress();
            
            // Listen for Livewire events
            window.addEventListener('audio-play', (e) => this.play(e.detail));
            window.addEventListener('audio-pause', () => this.pause());
            window.addEventListener('audio-seek', (e) => this.seek(e.detail.time));
            window.addEventListener('audio-volume', (e) => this.setVolumeLevel(e.detail.volume));
            window.addEventListener('audio-rate', (e) => this.setRate(e.detail.rate));
        },
        
        setupAudioListeners() {
            this.audio.addEventListener('timeupdate', () => {
                this.currentTime = this.audio.currentTime;
                this.updateProgress();
                @this.call('updateTime', this.currentTime);
            });
            
            this.audio.addEventListener('ended', () => {
                this.isPlaying = false;
                this.currentTime = 0;
                this.updateProgress();
            });
        },
        
        play(details) {
            this.audio.currentTime = details.time;
            this.audio.playbackRate = details.rate;
            this.audio.play();
        },
        
        pause() {
            this.audio.pause();
        },
        
        seek(time) {
            this.audio.currentTime = time;
            this.currentTime = time;
            this.updateProgress();
        },
        
        seekToPosition(event) {
            const rect = event.currentTarget.getBoundingClientRect();
            const percentage = ((event.clientX - rect.left) / rect.width) * 100;
            const time = (percentage / 100) * this.duration;
            @this.call('seek', percentage);
        },
        
        setVolume(value) {
            this.volume = value;
            this.audio.volume = value / 100;
            @this.call('setVolume', value);
        },
        
        setVolumeLevel(level) {
            this.audio.volume = level;
        },
        
        setPlaybackRate(rate) {
            this.playbackRate = rate;
            this.audio.playbackRate = rate;
            @this.call('setPlaybackRate', rate);
        },
        
        setRate(rate) {
            this.audio.playbackRate = rate;
        },
        
        toggleMute() {
            if (this.volume > 0) {
                this.previousVolume = this.volume;
                this.setVolume(0);
            } else {
                this.setVolume(this.previousVolume || 75);
            }
        },
        
        updateProgress() {
            this.progressPercentage = (this.currentTime / this.duration) * 100;
        },
        
        formatTime(seconds) {
            const mins = Math.floor(seconds / 60);
            const secs = Math.floor(seconds % 60);
            return `${mins}:${secs.toString().padStart(2, '0')}`;
        }
    }
}
</script>
@endpush