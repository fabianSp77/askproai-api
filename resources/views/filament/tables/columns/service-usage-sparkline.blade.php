<div x-data="{
    usage: {{ json_encode($getState() ?? [45, 52, 38, 65, 72, 88, 94]) }},
    max: 100,
    hoveredIndex: null,
    
    getPath() {
        if (!this.usage.length) return '';
        
        const width = 120;
        const height = 40;
        const points = this.usage.map((value, index) => {
            const x = (index / (this.usage.length - 1)) * width;
            const y = height - ((value / this.max) * height);
            return `${x},${y}`;
        });
        
        return `M ${points.join(' L ')}`;
    },
    
    getGradientStops() {
        const average = this.usage.reduce((a, b) => a + b, 0) / this.usage.length;
        if (average > 70) return ['#10b981', '#34d399']; // green
        if (average > 40) return ['#3b82f6', '#60a5fa']; // blue  
        return ['#f59e0b', '#fbbf24']; // amber
    }
}" class="relative group">
    
    <!-- Main Container -->
    <div class="flex items-center gap-3">
        <!-- Sparkline Chart -->
        <div class="relative">
            <svg width="120" height="40" class="overflow-visible">
                <!-- Gradient Definition -->
                <defs>
                    <linearGradient id="sparklineGradient" x1="0%" y1="0%" x2="0%" y2="100%">
                        <stop offset="0%" :style="'stop-color:' + getGradientStops()[0] + ';stop-opacity:0.8'" />
                        <stop offset="100%" :style="'stop-color:' + getGradientStops()[1] + ';stop-opacity:0.3'" />
                    </linearGradient>
                </defs>
                
                <!-- Area Fill -->
                <path :d="getPath() + ' L 120,40 L 0,40 Z'" 
                      fill="url(#sparklineGradient)" 
                      class="opacity-30" />
                
                <!-- Line -->
                <path :d="getPath()" 
                      fill="none" 
                      :stroke="getGradientStops()[0]" 
                      stroke-width="2"
                      class="transition-all duration-300" />
                
                <!-- Interactive Points -->
                <template x-for="(value, index) in usage" :key="index">
                    <circle :cx="(index / (usage.length - 1)) * 120"
                            :cy="40 - ((value / max) * 40)"
                            r="3"
                            :fill="getGradientStops()[0]"
                            class="opacity-0 group-hover:opacity-100 transition-opacity cursor-pointer"
                            @mouseenter="hoveredIndex = index"
                            @mouseleave="hoveredIndex = null">
                    </circle>
                </template>
            </svg>
            
            <!-- Tooltip -->
            <div x-show="hoveredIndex !== null"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-2 py-1 bg-gray-900 text-white text-xs rounded whitespace-nowrap z-10">
                <span x-text="usage[hoveredIndex] + '% Auslastung'"></span>
                <div class="absolute top-full left-1/2 transform -translate-x-1/2 -mt-1">
                    <div class="w-2 h-2 bg-gray-900 transform rotate-45"></div>
                </div>
            </div>
        </div>
        
        <!-- Current Value -->
        <div class="text-right">
            <div class="text-lg font-bold text-gray-900 dark:text-white" x-text="usage[usage.length - 1] + '%'"></div>
            <div class="text-xs text-gray-500 dark:text-gray-400">Aktuell</div>
        </div>
    </div>
    
    <!-- Trend Indicator -->
    <div class="absolute -top-2 -right-2 opacity-0 group-hover:opacity-100 transition-opacity">
        <div x-show="usage[usage.length - 1] > usage[usage.length - 2]" 
             class="bg-emerald-100 dark:bg-emerald-900/50 text-emerald-600 dark:text-emerald-400 rounded-full p-1">
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path>
            </svg>
        </div>
        <div x-show="usage[usage.length - 1] < usage[usage.length - 2]" 
             class="bg-red-100 dark:bg-red-900/50 text-red-600 dark:text-red-400 rounded-full p-1">
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
            </svg>
        </div>
        <div x-show="usage[usage.length - 1] === usage[usage.length - 2]" 
             class="bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 rounded-full p-1">
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14"></path>
            </svg>
        </div>
    </div>
</div>
