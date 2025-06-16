<div x-data="{
    status: '{{ $getState() }}',
    isAnimating: false,
    
    init() {
        setInterval(() => {
            this.isAnimating = true;
            setTimeout(() => this.isAnimating = false, 1000);
        }, 5000);
    }
}" class="flex items-center gap-2">
    
    <!-- Status Indicator -->
    <div class="relative">
        <div :class="[
            'w-3 h-3 rounded-full',
            status === 'active' ? 'bg-emerald-500' : 
            status === 'inactive' ? 'bg-gray-400' : 
            status === 'pending' ? 'bg-amber-500' : 'bg-red-500',
            isAnimating && status === 'active' ? 'animate-ping' : ''
        ]"></div>
        
        <div :class="[
            'absolute inset-0 rounded-full',
            status === 'active' ? 'bg-emerald-500' : 
            status === 'inactive' ? 'bg-gray-400' : 
            status === 'pending' ? 'bg-amber-500' : 'bg-red-500',
            'animate-ping opacity-75'
        ]" x-show="status === 'active'"></div>
    </div>
    
    <!-- Status Text -->
    <span :class="[
        'text-sm font-medium',
        status === 'active' ? 'text-emerald-600 dark:text-emerald-400' : 
        status === 'inactive' ? 'text-gray-600 dark:text-gray-400' : 
        status === 'pending' ? 'text-amber-600 dark:text-amber-400' : 'text-red-600 dark:text-red-400'
    ]" x-text="
        status === 'active' ? 'Live' : 
        status === 'inactive' ? 'Inaktiv' : 
        status === 'pending' ? 'Ausstehend' : 'Fehler'
    "></span>
    
    <!-- Additional Info -->
    <div x-show="status === 'active'" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         class="flex items-center gap-1 text-xs text-gray-500 dark:text-gray-400">
        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
        </svg>
        <span>Synchronisiert</span>
    </div>
</div>
