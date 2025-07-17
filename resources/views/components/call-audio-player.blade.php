@props(['call'])

@if($call->audio_url || $call->recording_url)
<div class="bg-white shadow rounded-lg overflow-hidden" x-data="audioPlayer()">
    <div class="bg-gradient-to-r from-indigo-50 to-purple-50 px-6 py-4 border-b border-gray-200">
        <h2 class="text-lg font-medium text-gray-900">Gesprächsaufzeichnung</h2>
    </div>
    <div class="p-6">
        <div class="bg-gray-50 rounded-lg p-4">
            {{-- Audio Element --}}
            <audio x-ref="audio" 
                   src="{{ $call->audio_url ?? $call->recording_url }}"
                   @timeupdate="updateProgress()"
                   @loadedmetadata="initializePlayer()"
                   @ended="isPlaying = false"
                   class="hidden">
            </audio>
            
            {{-- Player Controls --}}
            <div class="flex items-center space-x-4">
                {{-- Play/Pause Button --}}
                <button @click="togglePlay()" 
                        class="flex-shrink-0 w-12 h-12 rounded-full bg-indigo-600 text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                    <svg x-show="!isPlaying" class="w-6 h-6 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd" />
                    </svg>
                    <svg x-show="isPlaying" x-cloak class="w-6 h-6 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zM7 8a1 1 0 012 0v4a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v4a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                </button>
                
                {{-- Progress Bar --}}
                <div class="flex-1">
                    <div class="relative">
                        {{-- Time Display --}}
                        <div class="flex justify-between text-xs text-gray-500 mb-1">
                            <span x-text="formatTime(currentTime)">0:00</span>
                            <span x-text="formatTime(duration)">0:00</span>
                        </div>
                        
                        {{-- Progress Bar Background --}}
                        <div class="relative h-2 bg-gray-200 rounded-full overflow-hidden cursor-pointer" 
                             @click="seek($event)">
                            {{-- Progress Fill --}}
                            <div class="absolute left-0 top-0 h-full bg-indigo-600 transition-all duration-100"
                                 :style="`width: ${progress}%`">
                            </div>
                            
                            {{-- Progress Handle --}}
                            <div class="absolute top-1/2 transform -translate-y-1/2 w-4 h-4 bg-white border-2 border-indigo-600 rounded-full shadow-md transition-all duration-100"
                                 :style="`left: calc(${progress}% - 8px)`">
                            </div>
                        </div>
                    </div>
                </div>
                
                {{-- Speed Control --}}
                <div class="flex-shrink-0">
                    <select @change="changeSpeed($event)" 
                            class="text-sm border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="0.5">0.5x</option>
                        <option value="0.75">0.75x</option>
                        <option value="1" selected>1x</option>
                        <option value="1.25">1.25x</option>
                        <option value="1.5">1.5x</option>
                        <option value="2">2x</option>
                    </select>
                </div>
                
                {{-- Volume Control --}}
                <div class="flex-shrink-0 flex items-center space-x-2">
                    <button @click="toggleMute()" class="text-gray-500 hover:text-gray-700">
                        <svg x-show="!isMuted" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M9.383 3.076A1 1 0 0110 4v12a1 1 0 01-1.707.707L4.586 13H2a1 1 0 01-1-1V8a1 1 0 011-1h2.586l3.707-3.707a1 1 0 011.09-.217zM14.657 2.929a1 1 0 011.414 0A9.972 9.972 0 0119 10a9.972 9.972 0 01-2.929 7.071 1 1 0 01-1.414-1.414A7.971 7.971 0 0017 10c0-2.21-.894-4.208-2.343-5.657a1 1 0 010-1.414zm-2.829 2.828a1 1 0 011.415 0A5.983 5.983 0 0115 10a5.984 5.984 0 01-1.757 4.243 1 1 0 01-1.415-1.415A3.984 3.984 0 0013 10a3.983 3.983 0 00-1.172-2.828 1 1 0 010-1.415z" clip-rule="evenodd" />
                        </svg>
                        <svg x-show="isMuted" x-cloak class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M9.383 3.076A1 1 0 0110 4v12a1 1 0 01-1.707.707L4.586 13H2a1 1 0 01-1-1V8a1 1 0 011-1h2.586l3.707-3.707a1 1 0 011.09-.217zM12.293 7.293a1 1 0 011.414 0L15 8.586l1.293-1.293a1 1 0 111.414 1.414L16.414 10l1.293 1.293a1 1 0 01-1.414 1.414L15 11.414l-1.293 1.293a1 1 0 01-1.414-1.414L13.586 10l-1.293-1.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>
                    <input type="range" 
                           x-model="volume" 
                           @input="changeVolume()"
                           min="0" 
                           max="100" 
                           class="w-20 h-1 bg-gray-200 rounded-lg appearance-none cursor-pointer">
                </div>
            </div>
            
            {{-- Call Duration Info --}}
            <div class="mt-4 text-sm text-gray-500">
                <span>Anrufdauer: {{ gmdate('i:s', $call->duration_sec ?? 0) }} Minuten</span>
                @if($call->created_at)
                <span class="ml-4">Datum: {{ $call->created_at->format('d.m.Y H:i') }}</span>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
function audioPlayer() {
    return {
        isPlaying: false,
        isMuted: false,
        currentTime: 0,
        duration: 0,
        progress: 0,
        volume: 100,
        
        initializePlayer() {
            this.duration = this.$refs.audio.duration;
            this.$refs.audio.volume = this.volume / 100;
        },
        
        togglePlay() {
            if (this.isPlaying) {
                this.$refs.audio.pause();
            } else {
                this.$refs.audio.play();
            }
            this.isPlaying = !this.isPlaying;
        },
        
        updateProgress() {
            this.currentTime = this.$refs.audio.currentTime;
            this.progress = (this.currentTime / this.duration) * 100;
        },
        
        seek(event) {
            const rect = event.currentTarget.getBoundingClientRect();
            const x = event.clientX - rect.left;
            const clickedPercentage = (x / rect.width) * 100;
            const seekTime = (clickedPercentage / 100) * this.duration;
            
            this.$refs.audio.currentTime = seekTime;
            this.progress = clickedPercentage;
        },
        
        changeSpeed(event) {
            this.$refs.audio.playbackRate = parseFloat(event.target.value);
        },
        
        toggleMute() {
            this.isMuted = !this.isMuted;
            this.$refs.audio.muted = this.isMuted;
        },
        
        changeVolume() {
            this.$refs.audio.volume = this.volume / 100;
            if (this.volume > 0) {
                this.isMuted = false;
                this.$refs.audio.muted = false;
            }
        },
        
        formatTime(seconds) {
            const minutes = Math.floor(seconds / 60);
            const remainingSeconds = Math.floor(seconds % 60);
            return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`;
        }
    }
}
</script>
@endif