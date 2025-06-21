<x-filament-panels::page>
    {{-- Removed setupComplete check - always show the form --}}
        <div class="mb-8">
            <div class="bg-primary-50 dark:bg-primary-900/20 border border-primary-200 dark:border-primary-800 rounded-xl p-6">
                <h2 class="text-xl font-bold text-primary-900 dark:text-primary-100 mb-2">
                    🚀 Willkommen beim 3-Minuten Setup!
                </h2>
                <p class="text-primary-700 dark:text-primary-300">
                    In nur 4 einfachen Schritten ist Ihr KI-Telefon-System einsatzbereit. 
                    Wir haben bereits alles für Ihre Branche vorkonfiguriert.
                </p>
                <div class="mt-2 text-sm text-green-600 dark:text-green-400 font-medium">
                    <x-heroicon-o-check-circle class="w-4 h-4 inline-block mr-1" />
                    Ihre Eingaben werden automatisch bei jedem Schritt gespeichert.
                </div>
                
                <div class="mt-4 grid grid-cols-1 sm:grid-cols-4 gap-4">
                    <div class="flex items-center space-x-2">
                        <x-heroicon-o-clock class="w-5 h-5 text-primary-600" />
                        <span class="text-sm font-medium">⏱️ Nur 3 Minuten</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <x-heroicon-o-shield-check class="w-5 h-5 text-primary-600" />
                        <span class="text-sm font-medium">🔒 Sicher & DSGVO</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <x-heroicon-o-cog class="w-5 h-5 text-primary-600" />
                        <span class="text-sm font-medium">⚙️ Vorkonfiguriert</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <x-heroicon-o-phone class="w-5 h-5 text-primary-600" />
                        <span class="text-sm font-medium">📞 Sofort live</span>
                    </div>
                </div>
            </div>
        </div>
        
        {{-- Progress Indicator --}}
        <div wire:loading.flex wire:target="completeSetup" class="fixed inset-0 z-50 bg-gray-900/50 items-center justify-center">
            <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-xl">
                <div class="flex items-center space-x-3">
                    <svg class="animate-spin h-8 w-8 text-primary-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <div>
                        <p class="text-lg font-semibold text-gray-900 dark:text-white">Setup läuft...</p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ $this->getProgressMessage() }}</p>
                    </div>
                </div>
                <div class="mt-4 w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                    <div class="bg-primary-600 h-2 rounded-full transition-all duration-500" style="width: {{ $this->getProgressPercentage() }}%"></div>
                </div>
            </div>
        </div>
        
        <form wire:submit.prevent="completeSetup">
            {{ $this->form }}
        </form>
        
        <div class="mt-8 text-center text-sm text-gray-600 dark:text-gray-400">
            <p>
                Brauchen Sie Hilfe? 
                <a href="#" class="text-primary-600 hover:text-primary-700 font-medium">
                    Support kontaktieren
                </a>
                oder schauen Sie in unsere 
                <a href="#" class="text-primary-600 hover:text-primary-700 font-medium">
                    Anleitung
                </a>
            </p>
        </div>
    
    @push('scripts')
    <script>
        // Track setup time
        let setupStartTime = Date.now();
        
        // Show elapsed time
        setInterval(() => {
            const elapsed = Math.floor((Date.now() - setupStartTime) / 1000);
            const minutes = Math.floor(elapsed / 60);
            const seconds = elapsed % 60;
            
            const timeDisplay = document.getElementById('setup-timer');
            if (timeDisplay) {
                timeDisplay.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
            }
        }, 1000);
    </script>
    @endpush
</x-filament-panels::page>