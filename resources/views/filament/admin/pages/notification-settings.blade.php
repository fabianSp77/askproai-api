<x-filament-panels::page>
    <form wire:submit="save">
        {{ $this->form }}
        
        <div class="mt-6 flex justify-end">
            <x-filament::button type="submit" size="lg">
                Einstellungen speichern
            </x-filament::button>
        </div>
    </form>
    
    <div class="mt-8 border-t pt-8">
        <h3 class="text-lg font-medium mb-4">Test-Center</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <x-filament::card>
                <div class="flex items-center justify-between">
                    <div>
                        <h4 class="font-medium">Twilio Test Center</h4>
                        <p class="text-sm text-gray-500 dark:text-gray-400">SMS und WhatsApp testen</p>
                    </div>
                    <x-filament::button 
                        size="sm" 
                        color="gray"
                        wire:click="$dispatch('open-url', { url: '{{ route('filament.admin.pages.twilio-test-center') }}' })"
                    >
                        Öffnen
                    </x-filament::button>
                </div>
            </x-filament::card>
            
            <x-filament::card>
                <div class="flex items-center justify-between">
                    <div>
                        <h4 class="font-medium">Cal.com Dashboard</h4>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Workflows konfigurieren</p>
                    </div>
                    <x-filament::button 
                        size="sm" 
                        color="gray"
                        tag="a"
                        href="https://app.cal.com/workflows"
                        target="_blank"
                    >
                        Cal.com öffnen
                    </x-filament::button>
                </div>
            </x-filament::card>
        </div>
    </div>
    
    @push('scripts')
    <script>
        window.addEventListener('open-url', event => {
            window.location.href = event.detail.url;
        });
    </script>
    @endpush
</x-filament-panels::page>