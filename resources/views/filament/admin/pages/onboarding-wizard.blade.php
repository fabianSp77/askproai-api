<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Progress Bar --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium">Ihr Fortschritt</h3>
                <span class="text-sm text-gray-500">{{ $this->progressPercentage }}% abgeschlossen</span>
            </div>
            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                <div class="bg-primary-600 h-3 rounded-full transition-all duration-500 ease-out" 
                     style="width: {{ $this->progressPercentage }}%"></div>
            </div>
        </div>

        {{-- Wizard Form --}}
        <form wire:submit="save">
            {{ $this->form }}
        </form>

        {{-- Help Section --}}
        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 mt-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <x-heroicon-o-information-circle class="h-5 w-5 text-blue-400" />
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-blue-800 dark:text-blue-200">
                        Brauchen Sie Hilfe?
                    </h3>
                    <div class="mt-2 text-sm text-blue-700 dark:text-blue-300">
                        <p>
                            Unser Support-Team steht Ihnen gerne zur Verfügung. 
                            <a href="#" class="font-medium underline" wire:click="$dispatch('open-modal', { id: 'support-chat' })">
                                Chat starten
                            </a> oder rufen Sie uns an unter 
                            <a href="tel:+4930123456789" class="font-medium underline">+49 30 123456789</a>.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        // Auto-save functionality
        let saveTimeout;
        document.addEventListener('input', function(e) {
            if (e.target.closest('form')) {
                clearTimeout(saveTimeout);
                saveTimeout = setTimeout(() => {
                    @this.call('save');
                }, 3000);
            }
        });
    </script>
    @endpush
</x-filament-panels::page>