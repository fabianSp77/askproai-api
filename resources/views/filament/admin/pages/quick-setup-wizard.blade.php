<x-filament-panels::page>
    @if($this->setupComplete)
        <div class="bg-success-50 dark:bg-success-900/20 border border-success-200 dark:border-success-800 rounded-xl p-6">
            <div class="flex items-center space-x-3">
                <div class="flex-shrink-0">
                    <x-heroicon-o-check-circle class="w-8 h-8 text-success-600 dark:text-success-400" />
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-success-900 dark:text-success-100">
                        Setup bereits abgeschlossen!
                    </h3>
                    <p class="mt-1 text-sm text-success-700 dark:text-success-300">
                        Ihr System ist bereits eingerichtet und einsatzbereit.
                    </p>
                </div>
            </div>
            
            <div class="mt-6 flex flex-col sm:flex-row gap-3">
                <x-filament::button
                    href="/admin"
                    tag="a"
                    size="lg"
                    icon="heroicon-o-chart-bar"
                >
                    Zum Dashboard
                </x-filament::button>
                
                <x-filament::button
                    href="{{ route('filament.admin.resources.branches.index') }}"
                    tag="a"
                    size="lg"
                    color="gray"
                    icon="heroicon-o-building-office"
                >
                    Filialen verwalten
                </x-filament::button>
            </div>
        </div>
    @else
        <div class="mb-8">
            <div class="bg-primary-50 dark:bg-primary-900/20 border border-primary-200 dark:border-primary-800 rounded-xl p-6">
                <h2 class="text-xl font-bold text-primary-900 dark:text-primary-100 mb-2">
                    🚀 Willkommen beim 3-Minuten Setup!
                </h2>
                <p class="text-primary-700 dark:text-primary-300">
                    In nur 4 einfachen Schritten ist Ihr KI-Telefon-System einsatzbereit. 
                    Wir haben bereits alles für Ihre Branche vorkonfiguriert.
                </p>
                
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
    @endif
    
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