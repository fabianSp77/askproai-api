<div x-data="{
    duration: @entangle($getStatePath()),
    segments: [],
    activeSegment: null,
    suggestedDurations: [15, 30, 45, 60, 90, 120],
    animating: false,
    
    init() {
        this.updateSegments();
        this.$watch('duration', () => {
            this.animating = true;
            setTimeout(() => {
                this.updateSegments();
                this.animating = false;
            }, 300);
        });
    },
    
    updateSegments() {
        const totalMinutes = parseInt(this.duration) || 30;
        const segmentCount = Math.min(Math.ceil(totalMinutes / 15), 8);
        this.segments = Array.from({ length: segmentCount }, (_, i) => ({
            id: i,
            minutes: Math.min(15, totalMinutes - (i * 15)),
            label: this.getSegmentLabel(i, totalMinutes),
            color: this.getSegmentColor(i, segmentCount)
        }));
    },
    
    getSegmentLabel(index, total) {
        const start = index * 15;
        const end = Math.min((index + 1) * 15, total);
        return `${start}-${end} Min`;
    },
    
    getSegmentColor(index, total) {
        const colors = [
            'from-blue-400 to-blue-500',
            'from-indigo-400 to-indigo-500',
            'from-purple-400 to-purple-500',
            'from-pink-400 to-pink-500',
            'from-rose-400 to-rose-500',
            'from-orange-400 to-orange-500',
            'from-amber-400 to-amber-500',
            'from-yellow-400 to-yellow-500'
        ];
        return colors[index % colors.length];
    },
    
    setDuration(value) {
        this.duration = value;
        $wire.set('{{ $getStatePath() }}', value);
    }
}" class="space-y-6">
    
    <!-- Header mit Animation -->
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="relative">
                <div class="absolute inset-0 bg-blue-400 dark:bg-blue-600 rounded-full blur-md opacity-50 animate-pulse"></div>
                <div class="relative bg-gradient-to-r from-blue-500 to-indigo-500 text-white p-2 rounded-full">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
            <div>
                <h4 class="text-sm font-semibold text-gray-900 dark:text-white">Service-Dauer Timeline</h4>
                <p class="text-xs text-gray-500 dark:text-gray-400">Visualisierung der Zeitblöcke</p>
            </div>
        </div>
        <div class="text-2xl font-bold text-gray-900 dark:text-white" x-text="duration + ' Min'"></div>
    </div>

    <!-- Visual Timeline -->
    <div class="relative">
        <div class="flex gap-1 p-4 bg-gray-50 dark:bg-gray-800/50 rounded-xl overflow-hidden">
            <template x-for="segment in segments" :key="segment.id">
                <div @mouseenter="activeSegment = segment.id"
                     @mouseleave="activeSegment = null"
                     :class="[
                         'relative flex-1 h-20 rounded-lg overflow-hidden cursor-pointer transition-all duration-300',
                         activeSegment === segment.id ? 'scale-105 shadow-lg' : 'scale-100',
                         animating ? 'animate-pulse' : ''
                     ]">
                    
                    <!-- Gradient Background -->
                    <div :class="'absolute inset-0 bg-gradient-to-br ' + segment.color"></div>
                    
                    <!-- Content -->
                    <div class="relative h-full flex flex-col items-center justify-center text-white">
                        <div class="text-xs font-medium" x-text="segment.label"></div>
                        <div class="text-lg font-bold" x-text="segment.minutes + ' Min'"></div>
                    </div>
                    
                    <!-- Hover Effect -->
                    <div x-show="activeSegment === segment.id"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0"
                         x-transition:enter-end="opacity-100"
                         class="absolute inset-0 bg-white/20 backdrop-blur-sm flex items-center justify-center">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </template>
        </div>
        
        <!-- Progress Bar -->
        <div class="absolute bottom-0 left-0 right-0 h-1 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
            <div class="h-full bg-gradient-to-r from-blue-500 to-indigo-500 transition-all duration-500"
                 :style="'width: ' + Math.min((duration / 120) * 100, 100) + '%'"></div>
        </div>
    </div>

    <!-- Quick Duration Buttons -->
    <div class="space-y-3">
        <div class="flex items-center justify-between">
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Vorschläge basierend auf ähnlichen Services</span>
            <button type="button" 
                    @click="$dispatch('open-modal', { id: 'duration-ai-assistant' })"
                    class="text-xs text-blue-600 dark:text-blue-400 hover:underline flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                </svg>
                KI-Assistent
            </button>
        </div>
        
        <div class="grid grid-cols-3 md:grid-cols-6 gap-2">
            <template x-for="suggested in suggestedDurations" :key="suggested">
                <button type="button"
                        @click="setDuration(suggested)"
                        :class="[
                            'relative overflow-hidden group px-3 py-2 rounded-lg text-sm font-medium transition-all duration-300',
                            duration == suggested 
                                ? 'bg-gradient-to-r from-blue-500 to-indigo-500 text-white shadow-lg scale-105' 
                                : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 hover:border-blue-400 dark:hover:border-blue-500'
                        ]">
                    <span class="relative z-10" x-text="suggested + ' Min'"></span>
                    <div x-show="duration != suggested"
                         class="absolute inset-0 bg-gradient-to-r from-blue-400 to-indigo-400 opacity-0 group-hover:opacity-10 transition-opacity duration-300"></div>
                </button>
            </template>
        </div>
    </div>

    <!-- Info Card -->
    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-xl p-4 border border-blue-200 dark:border-blue-800">
        <div class="flex items-start gap-3">
            <div class="flex-shrink-0">
                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <div class="flex-1">
                <h5 class="text-sm font-medium text-blue-900 dark:text-blue-100">Optimale Zeitplanung</h5>
                <p class="text-xs text-blue-700 dark:text-blue-300 mt-1">
                    Die Dauer beeinflusst direkt die Verfügbarkeit und Auslastung. 
                    Kürzere Services ermöglichen mehr Buchungen, längere Services bieten mehr Zeit für qualitativ hochwertige Arbeit.
                </p>
            </div>
        </div>
    </div>
</div>

<!-- KI-Assistent Modal (Placeholder) -->
<div x-data="{ open: false }" 
     @open-modal.window="if ($event.detail.id === 'duration-ai-assistant') open = true"
     @close-modal.window="open = false"
     x-show="open"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fixed inset-0 z-50 overflow-y-auto"
     style="display: none;">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-black opacity-50" @click="open = false"></div>
        
        <div class="relative bg-white dark:bg-gray-800 rounded-2xl max-w-md w-full p-6 shadow-2xl"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 transform scale-95"
             x-transition:enter-end="opacity-100 transform scale-100">
            
            <div class="text-center">
                <div class="mx-auto w-16 h-16 bg-gradient-to-r from-blue-500 to-indigo-500 rounded-full flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">KI-Dauer-Assistent</h3>
                <p class="text-sm text-gray-600 dark:text-gray-300">
                    Der KI-Assistent analysiert ähnliche Services und schlägt die optimale Dauer vor.
                </p>
                <button @click="open = false" 
                        class="mt-4 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    Verstanden
                </button>
            </div>
        </div>
    </div>
</div>
