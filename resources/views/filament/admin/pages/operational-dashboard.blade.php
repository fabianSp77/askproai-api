<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Real-time Status Bar --}}
        <div class="bg-gradient-to-r from-blue-600 to-blue-800 rounded-xl p-4 text-white">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-6">
                    <div class="flex items-center space-x-2">
                        <div class="relative">
                            <div class="w-3 h-3 bg-green-400 rounded-full"></div>
                            <div class="absolute inset-0 w-3 h-3 bg-green-400 rounded-full animate-ping"></div>
                        </div>
                        <span class="text-sm font-medium">System Online</span>
                    </div>
                    <div class="h-4 w-px bg-white/30"></div>
                    <div class="text-sm">
                        <span class="opacity-80">Last Update:</span>
                        <span class="font-medium ml-1">{{ now()->format('H:i:s') }}</span>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <button wire:click="refresh" class="p-2 hover:bg-white/10 rounded-lg transition-colors">
                        <x-heroicon-m-arrow-path class="w-5 h-5" />
                    </button>
                </div>
            </div>
        </div>

        {{-- Widgets Grid --}}
        <div class="grid grid-cols-1 gap-6">
            @foreach($this->getWidgets() as $widget)
                @livewire($widget)
            @endforeach
        </div>
    </div>

    @push('scripts')
        <script>
            // Auto-refresh countdown
            let refreshInterval = 30; // seconds
            let countdown = refreshInterval;
            
            setInterval(() => {
                countdown--;
                if (countdown <= 0) {
                    countdown = refreshInterval;
                }
                
                // Update any countdown displays if needed
            }, 1000);
        </script>
    @endpush
</x-filament-panels::page>