<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Progress Overview --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
                        Einrichtungsfortschritt
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        {{ $completedSteps }} von {{ $totalSteps }} Schritten abgeschlossen
                    </p>
                </div>
                <div class="text-3xl font-bold text-primary-600">
                    {{ $progressPercentage }}%
                </div>
            </div>
            
            {{-- Progress Bar --}}
            <div class="w-full bg-gray-200 rounded-full h-3 dark:bg-gray-700">
                <div class="bg-primary-600 h-3 rounded-full transition-all duration-300" 
                     style="width: {{ $progressPercentage }}%"></div>
            </div>
            
            @if($nextStep)
                <div class="mt-4 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                    <p class="text-sm text-blue-800 dark:text-blue-200">
                        <span class="font-semibold">Nächster empfohlener Schritt:</span> 
                        {{ $nextStep['title'] }}
                    </p>
                </div>
            @else
                <div class="mt-4 p-4 bg-green-50 dark:bg-green-900/20 rounded-lg">
                    <p class="text-sm text-green-800 dark:text-green-200">
                        <span class="font-semibold">🎉 Glückwunsch!</span> 
                        Ihre Einrichtung ist vollständig abgeschlossen.
                    </p>
                </div>
            @endif
        </div>
        
        {{-- Quick Actions --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <x-filament::section>
                <x-slot name="heading">
                    Schnellstart-Assistent
                </x-slot>
                <x-slot name="description">
                    Lassen Sie sich durch die komplette Einrichtung führen
                </x-slot>
                
                <x-filament::button
                    wire:click="runQuickSetup"
                    icon="heroicon-o-rocket-launch"
                    size="lg"
                    class="w-full"
                >
                    Schnellstart-Assistent starten
                </x-filament::button>
            </x-filament::section>
            
            <x-filament::section>
                <x-slot name="heading">
                    Telefonsystem testen
                </x-slot>
                <x-slot name="description">
                    Testen Sie Ihren KI-Telefonassistenten mit einem Probeanruf
                </x-slot>
                
                <x-filament::button
                    wire:click="testPhoneSystem"
                    icon="heroicon-o-phone-arrow-up-right"
                    size="lg"
                    color="success"
                    class="w-full"
                >
                    Testanruf starten
                </x-filament::button>
            </x-filament::section>
        </div>
        
        {{-- Setup Steps Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($setupSteps as $key => $step)
                <a href="{{ $step['url'] }}" 
                   class="block group">
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 
                                hover:shadow-md transition-all duration-200 
                                {{ $step['completed'] ? 'border-2 border-green-500' : 'border-2 border-gray-200 dark:border-gray-700' }}">
                        <div class="flex items-start justify-between mb-4">
                            <div class="p-3 rounded-lg 
                                        {{ $step['completed'] ? 'bg-green-100 dark:bg-green-900/30' : 'bg-gray-100 dark:bg-gray-700' }}">
                                <x-dynamic-component 
                                    :component="'heroicon-o-' . substr($step['icon'], 11)"
                                    class="w-6 h-6 {{ $step['completed'] ? 'text-green-600 dark:text-green-400' : 'text-gray-600 dark:text-gray-400' }}"
                                />
                            </div>
                            @if($step['completed'])
                                <x-heroicon-o-check-circle class="w-6 h-6 text-green-500" />
                            @else
                                <x-heroicon-o-arrow-right class="w-5 h-5 text-gray-400 
                                                                 group-hover:text-primary-600 
                                                                 transition-colors" />
                            @endif
                        </div>
                        
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                            {{ $step['title'] }}
                        </h3>
                        
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            {{ $step['description'] }}
                        </p>
                        
                        <div class="mt-4">
                            @if($step['completed'])
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full 
                                           text-xs font-medium bg-green-100 text-green-800 
                                           dark:bg-green-900/30 dark:text-green-400">
                                    Abgeschlossen
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full 
                                           text-xs font-medium bg-gray-100 text-gray-800 
                                           dark:bg-gray-700 dark:text-gray-300">
                                    Ausstehend
                                </span>
                            @endif
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
        
        {{-- Additional Resources --}}
        <x-filament::section>
            <x-slot name="heading">
                Zusätzliche Ressourcen
            </x-slot>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <a href="{{ route('filament.admin.pages.documentation-hub') }}" 
                   class="flex items-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg 
                          hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                    <x-heroicon-o-book-open class="w-5 h-5 text-gray-600 dark:text-gray-400 mr-3" />
                    <span class="text-sm font-medium text-gray-900 dark:text-white">
                        Dokumentation
                    </span>
                </a>
                
                <a href="{{ route('filament.admin.pages.integration-hub') }}" 
                   class="flex items-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg 
                          hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                    <x-heroicon-o-puzzle-piece class="w-5 h-5 text-gray-600 dark:text-gray-400 mr-3" />
                    <span class="text-sm font-medium text-gray-900 dark:text-white">
                        Integrationen
                    </span>
                </a>
                
                <a href="mailto:support@askproai.de" 
                   class="flex items-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg 
                          hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                    <x-heroicon-o-lifebuoy class="w-5 h-5 text-gray-600 dark:text-gray-400 mr-3" />
                    <span class="text-sm font-medium text-gray-900 dark:text-white">
                        Support kontaktieren
                    </span>
                </a>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>