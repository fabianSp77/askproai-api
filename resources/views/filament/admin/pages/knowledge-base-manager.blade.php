<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Statistics --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Artikel gesamt</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $this->getStats()['total'] }}</p>
                    </div>
                </div>
            </div>
            
            @foreach($this->getStats()['byCategory'] as $category => $count)
                <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow">
                    <div class="flex items-center">
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ $category }}</p>
                            <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $count }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        
        {{-- Editor Form --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h2 class="text-lg font-medium mb-4">Artikel bearbeiten</h2>
            
            <form wire:submit.prevent="save">
                {{ $this->form }}
                
                <div class="mt-6 flex items-center justify-between">
                    <div>
                        @if($this->topic && $this->topic !== 'new')
                            <x-filament::button
                                type="button"
                                color="danger"
                                wire:click="delete"
                                wire:confirm="Möchten Sie diesen Artikel wirklich löschen?"
                            >
                                Artikel löschen
                            </x-filament::button>
                        @endif
                    </div>
                    
                    <div class="flex items-center gap-4">
                        @if($this->topic)
                            <x-filament::link
                                href="/help/{{ $this->category }}/{{ $this->topic }}"
                                target="_blank"
                                icon="heroicon-m-arrow-top-right-on-square"
                            >
                                Vorschau
                            </x-filament::link>
                        @endif
                        
                        <x-filament::button type="submit">
                            Speichern
                        </x-filament::button>
                    </div>
                </div>
            </form>
        </div>
        
        {{-- Quick Links --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-medium mb-4">Schnellzugriff</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-filament::link
                    href="/help"
                    target="_blank"
                    icon="heroicon-m-arrow-top-right-on-square"
                    class="text-primary-600 hover:text-primary-500"
                >
                    Hilfe-Center öffnen
                </x-filament::link>
                
                <x-filament::link
                    href="{{ route('filament.admin.pages.customer-portal-management') }}"
                    icon="heroicon-m-key"
                    class="text-primary-600 hover:text-primary-500"
                >
                    Kundenportal verwalten
                </x-filament::link>
            </div>
        </div>
    </div>
</x-filament-panels::page>