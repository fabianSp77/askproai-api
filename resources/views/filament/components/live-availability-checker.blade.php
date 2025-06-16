<div 
    x-data="{
        checking: false,
        slots: [],
        suggestions: [],
        selectedSlot: null,
        
        async checkAvailability() {
            this.checking = true;
            
            // In real implementation, this would make an API call
            // For now, we'll use the PHP data
            setTimeout(() => {
                this.slots = @js($getAvailableSlots());
                this.suggestions = @js($getSuggestedSlots());
                this.checking = false;
            }, 300);
        },
        
        selectSlot(slot) {
            this.selectedSlot = slot;
            $wire.set('starts_at', slot.start);
            $wire.set('ends_at', slot.end);
        }
    }"
    x-init="checkAvailability()"
    wire:key="availability-checker-{{ now() }}"
    class="space-y-4"
>
    {{-- Loading State --}}
    <div x-show="checking" class="flex items-center justify-center py-8">
        <div class="relative">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"></div>
            <x-heroicon-o-calendar-days class="absolute inset-0 m-auto h-6 w-6 text-primary-600" />
        </div>
        <span class="ml-3 text-sm text-gray-600 dark:text-gray-400">Verfügbarkeiten werden geladen...</span>
    </div>
    
    {{-- Available Slots Grid --}}
    <div x-show="!checking && slots.length > 0" x-cloak>
        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
            Verfügbare Zeiten am {{ $date ? \Carbon\Carbon::parse($date)->format('d.m.Y') : 'ausgewählten Tag' }}
        </h4>
        
        <div class="grid grid-cols-4 md:grid-cols-6 lg:grid-cols-8 gap-2">
            <template x-for="slot in slots" :key="slot.display">
                <button
                    type="button"
                    @click="selectSlot(slot)"
                    :class="{
                        'ring-2 ring-primary-500 bg-primary-50 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300': selectedSlot && selectedSlot.display === slot.display,
                        'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-300 hover:bg-green-100 dark:hover:bg-green-900/40': !selectedSlot || selectedSlot.display !== slot.display
                    }"
                    class="relative px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200 transform hover:scale-105"
                >
                    <span x-text="slot.display"></span>
                    <div 
                        x-show="selectedSlot && selectedSlot.display === slot.display"
                        class="absolute -top-1 -right-1 w-3 h-3 bg-primary-500 rounded-full animate-pulse"
                    ></div>
                </button>
            </template>
        </div>
        
        {{-- Quick Actions --}}
        <div class="mt-4 flex items-center space-x-4">
            <button
                type="button"
                @click="checkAvailability()"
                class="text-sm text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300"
            >
                <x-heroicon-m-arrow-path class="inline-block w-4 h-4 mr-1" />
                Aktualisieren
            </button>
            
            @if($getNextAvailable())
                <button
                    type="button"
                    @click="selectSlot(@js($getNextAvailable()))"
                    class="text-sm text-green-600 hover:text-green-700 dark:text-green-400 dark:hover:text-green-300"
                >
                    <x-heroicon-m-bolt class="inline-block w-4 h-4 mr-1" />
                    Nächster freier Termin: {{ $getNextAvailable()['time'] }}
                </button>
            @endif
        </div>
    </div>
    
    {{-- No Slots Available --}}
    <div x-show="!checking && slots.length === 0" x-cloak>
        <div class="bg-amber-50 dark:bg-amber-900/20 rounded-lg p-4 border border-amber-200 dark:border-amber-800">
            <div class="flex items-start">
                <x-heroicon-m-exclamation-triangle class="w-5 h-5 text-amber-600 dark:text-amber-400 mt-0.5" />
                <div class="ml-3">
                    <h4 class="text-sm font-medium text-amber-800 dark:text-amber-200">
                        Keine freien Termine
                    </h4>
                    <p class="text-sm text-amber-700 dark:text-amber-300 mt-1">
                        Am ausgewählten Tag sind keine Termine mehr verfügbar.
                    </p>
                </div>
            </div>
        </div>
        
        {{-- Alternative Suggestions --}}
        <div x-show="suggestions.length > 0" class="mt-4">
            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                Alternative Tage mit freien Terminen
            </h4>
            
            <div class="space-y-3">
                <template x-for="suggestion in suggestions" :key="suggestion.date">
                    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-3 border border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-medium text-gray-900 dark:text-gray-100" x-text="new Date(suggestion.date).toLocaleDateString('de-DE', { weekday: 'long', day: 'numeric', month: 'long' })"></span>
                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                <span x-text="suggestion.slots.length"></span> Termine frei
                            </span>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <template x-for="slot in suggestion.slots" :key="slot.display">
                                <button
                                    type="button"
                                    @click="$wire.set('date', suggestion.date); selectSlot(slot)"
                                    class="px-3 py-1 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors"
                                >
                                    <x-heroicon-m-clock class="inline-block w-4 h-4 mr-1 text-gray-400" />
                                    <span x-text="slot.display"></span>
                                </button>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
    
    {{-- Selected Slot Confirmation --}}
    <div 
        x-show="selectedSlot" 
        x-cloak
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 transform scale-95"
        x-transition:enter-end="opacity-100 transform scale-100"
        class="mt-4 bg-primary-50 dark:bg-primary-900/20 rounded-lg p-3 border border-primary-200 dark:border-primary-800"
    >
        <div class="flex items-center">
            <x-heroicon-m-check-circle class="w-5 h-5 text-primary-600 dark:text-primary-400" />
            <span class="ml-2 text-sm font-medium text-primary-800 dark:text-primary-200">
                Ausgewählt: <span x-text="selectedSlot?.display"></span> Uhr
            </span>
        </div>
    </div>
</div>