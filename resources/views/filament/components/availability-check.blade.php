<div 
    x-data="{ 
        checking: false,
        result: null,
        checkAvailability() {
            this.checking = true;
            // Simulate availability check
            setTimeout(() => {
                this.result = {
                    available: Math.random() > 0.3,
                    conflicts: [],
                    suggestions: [
                        { time: '14:00', available: true },
                        { time: '15:00', available: true },
                        { time: '16:00', available: false },
                        { time: '17:00', available: true }
                    ]
                };
                this.checking = false;
            }, 500);
        }
    }"
    x-init="checkAvailability()"
    wire:change="checkAvailability"
    class="space-y-3"
>
    {{-- Loading State --}}
    <div x-show="checking" class="flex items-center justify-center py-4">
        <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-primary-600"></div>
        <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">Verfügbarkeit wird geprüft...</span>
    </div>
    
    {{-- Result --}}
    <div x-show="!checking && result" x-cloak>
        {{-- Available --}}
        <div x-show="result && result.available" class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4 border border-green-200 dark:border-green-800">
            <div class="flex items-start">
                <x-heroicon-m-check-circle class="w-5 h-5 text-green-600 dark:text-green-400 mt-0.5" />
                <div class="ml-3">
                    <h4 class="text-sm font-medium text-green-800 dark:text-green-200">Zeitslot verfügbar</h4>
                    <p class="text-sm text-green-700 dark:text-green-300 mt-1">
                        Der gewählte Termin kann gebucht werden.
                    </p>
                </div>
            </div>
        </div>
        
        {{-- Not Available --}}
        <div x-show="result && !result.available" class="bg-red-50 dark:bg-red-900/20 rounded-lg p-4 border border-red-200 dark:border-red-800">
            <div class="flex items-start">
                <x-heroicon-m-x-circle class="w-5 h-5 text-red-600 dark:text-red-400 mt-0.5" />
                <div class="ml-3">
                    <h4 class="text-sm font-medium text-red-800 dark:text-red-200">Zeitslot nicht verfügbar</h4>
                    <p class="text-sm text-red-700 dark:text-red-300 mt-1">
                        Es existiert bereits ein Termin zu dieser Zeit.
                    </p>
                </div>
            </div>
        </div>
        
        {{-- Alternative Suggestions --}}
        <div x-show="result && result.suggestions && result.suggestions.length > 0" class="mt-3">
            <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Alternative Zeiten:</p>
            <div class="grid grid-cols-4 gap-2">
                <template x-for="slot in result.suggestions" :key="slot.time">
                    <button
                        type="button"
                        :class="{
                            'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 hover:bg-green-200 dark:hover:bg-green-900/50': slot.available,
                            'bg-gray-100 dark:bg-gray-800 text-gray-400 dark:text-gray-600 cursor-not-allowed': !slot.available
                        }"
                        :disabled="!slot.available"
                        class="px-3 py-1.5 rounded-md text-sm font-medium transition-colors"
                        @click="slot.available && $dispatch('select-time', { time: slot.time })"
                        x-text="slot.time"
                    ></button>
                </template>
            </div>
        </div>
    </div>
</div>