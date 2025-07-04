<x-filament-panels::page>
    <form wire:submit="save">
        {{ $this->form }}

        <div class="mt-6">
            <x-filament::button type="submit" icon="heroicon-o-check">
                Einstellungen speichern
            </x-filament::button>
        </div>
    </form>
    
    @if(auth()->user()->auto_translate_content)
        <div class="mt-8">
            <x-filament::section>
                <x-slot name="heading">
                    Beispiel-Übersetzung
                </x-slot>
                
                <x-slot name="description">
                    So werden Ihre Anrufinhalte übersetzt angezeigt
                </x-slot>
                
                <div class="space-y-4">
                    <div>
                        <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Original (Englisch):</p>
                        <p class="text-sm text-gray-600 dark:text-gray-400 italic">
                            "Hello, I would like to schedule an appointment for next Tuesday."
                        </p>
                    </div>
                    
                    <div>
                        <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Übersetzung ({{ auth()->user()->content_language === 'de' ? 'Deutsch' : strtoupper(auth()->user()->content_language) }}):
                        </p>
                        <p class="text-sm text-gray-900 dark:text-gray-100">
                            @if(auth()->user()->content_language === 'de')
                                "Hallo, ich möchte gerne einen Termin für nächsten Dienstag vereinbaren."
                            @elseif(auth()->user()->content_language === 'es')
                                "Hola, me gustaría programar una cita para el próximo martes."
                            @elseif(auth()->user()->content_language === 'fr')
                                "Bonjour, j'aimerais prendre rendez-vous pour mardi prochain."
                            @else
                                "Hello, I would like to schedule an appointment for next Tuesday."
                            @endif
                        </p>
                    </div>
                </div>
            </x-filament::section>
        </div>
    @endif
</x-filament-panels::page>